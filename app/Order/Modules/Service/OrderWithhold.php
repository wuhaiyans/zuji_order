<?php
namespace App\Order\Modules\Service;

use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderWithhold
{

    /**
     * 用户还机代扣扣款
     * @param  int $instalment_id 分期ID
     * @return bool
     */
    public static function instalment_withhold($instalmentId ){
        if ( $instalmentId == "") {
            return false;
        }

        $remark         = "还机代扣剩余分期";

        // 查询分期信息
        $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            DB::rollBack();
            // 提交事务
            return false;
        }

        // 生成交易码
        $trade_no = createNo();
        // 扣款交易码
        if( $instalmentInfo['trade_no'] == '' ){
            // 1)记录租机交易码
            $b = OrderGoodsInstalment::save(['id'=>$instalmentId],['trade_no'=>$trade_no]);
            if( $b === false ){
                return false;
            }
            $instalmentInfo['trade_no'] = $trade_no;
        }
        $trade_no = $instalmentInfo['trade_no'];

        //开启事务
        DB::beginTransaction();

        // 订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            return false;
        }
        if($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService){
            DB::rollBack();
            return false;
        }

        //判断是否允许扣款
        $allow = OrderGoodsInstalment::allowWithhold($instalmentId);
        if(!$allow){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71000, "不允许扣款" );
        }

        // 商品
        $subject = '订单-'.$instalmentInfo['order_no'].'-'.$instalmentInfo['goods_no'].'-第'.$instalmentInfo['times'].'期扣款';

        // 价格
        $amount = $instalmentInfo['amount'] * 100;
        if( $amount<0 ){
            DB::rollBack();
            Log::error("扣款金额不能小于1分");
            return false;
        }

        //判断支付方式
        if( $orderInfo['pay_type'] == \App\Order\Modules\Inc\PayInc::MiniAlipay ){
            //获取订单的芝麻订单编号
            $miniOrderInfo = \App\Order\Modules\Repository\MiniOrderRentNotifyRepository::getMiniOrderRentNotify( $instalmentInfo['order_no'] );
            if( empty($miniOrderInfo) ){
                \App\Lib\Common\LogApi::info('本地小程序确认订单回调记录查询失败',$orderInfo['order_no']);
                Log::error("本地小程序确认订单回调记录查询失败");
                return false;
            }
            //芝麻小程序扣款请求
            $miniParams['out_order_no']     = $miniOrderInfo['out_order_no'];
            $miniParams['zm_order_no']      = $miniOrderInfo['zm_order_no'];
            //扣款交易号
            $miniParams['out_trans_no']     = $instalmentId;
            $miniParams['pay_amount']       = $amount;
            $miniParams['remark']           = $subject;
            $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );
            //判断请求发送是否成功
            if($pay_status == 'PAY_SUCCESS'){
                return true;
            }elseif($pay_status =='PAY_FAILED'){
                OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term']);
                Log::error("小程序扣款请求失败");
                return false;
            }elseif($pay_status == 'PAY_INPROGRESS'){
                Log::error("小程序扣款处理中请等待");
                return false;
            }else{
                Log::error("小程序扣款处理失败（内部失败）");
                return false;
            }
        }else {
            // 保存 备注，更新状态
            $data = [
                'remark'        => $remark,
                'payment_time'  => time(),
                'status'        => OrderInstalmentStatus::PAYING,// 扣款中
            ];
            $result = OrderGoodsInstalment::save(['id'=>$instalmentId],$data);
            if(!$result){
                DB::rollBack();
                Log::error("扣款备注保存失败");
                return false;
            }

            // 代扣协议编号
            $channel = \App\Order\Modules\Repository\Pay\Channel::Alipay;   //暂时保留
            // 查询用户协议
            $withhold = \App\Order\Modules\Repository\Pay\WithholdQuery::getByUserChannel($instalmentInfo['user_id'], $channel);

            $withholdInfo = $withhold->getData();

            $agreementNo = $withholdInfo['out_withhold_no'];
            if (!$agreementNo) {
                DB::rollBack();
                Log::error("用户代扣协议编号错误");
                return false;
            }
            // 代扣接口
            $withholding = new \App\Lib\Payment\CommonWithholdingApi;

            $backUrl = config('app.url') . "/order/pay/withholdCreatePayNotify";

            $withholding_data = [
                'agreement_no'  => $agreementNo,            //支付平台代扣协议号
                'out_trade_no'  => $trade_no,               //业务系统业务码
                'amount'        => $amount,                 //交易金额；单位：分
                'back_url'      => $backUrl,                //后台通知地址
                'name'          => $subject,                //交易备注
                'user_id'       => $orderInfo['user_id'],   //业务平台用户id
            ];

            try{
                // 请求代扣接口
                $withholding->deduct($withholding_data);

            }catch(\Exception $exc){
                DB::rollBack();
                \App\Lib\Common\LogApi::error('分期代扣错误', [$exc->getMessage()]);
                //捕获异常 买家余额不足
                if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term']);
                    Log::error("买家余额不足");
                    return false;
                } else {
                    Log::error("扣款失败");
                    return false;
                }
            }
        }
        // 提交事务
        DB::commit();
        return true;
    }


    /**
     * 提前还款异步回调接口
     * @requwet Array
     * [
     *      "business_type" =>"", 业务类型
     *      "business_no"   =>"", 业务编号
     * 		"status"        =>"", 支付状态
     * ]
     * @return json
     */
    public static function repaymentNotify($params){

        //过滤参数
        $rule = [
            'business_type'     => 'required',//业务类型
            'business_no'       => 'required',//业务编码
            'status'            => 'required',//支付状态
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return false;
        }
        if( $params['status'] != 'success' || $params['business_type'] != \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT ){
            return false;
        }


        // 支付成功
        if($params['status'] == "success"){

            $instalmentInfo = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['trade_no'=>$params['business_no']]);
            if( !is_array($instalmentInfo)){
                return false;
            }
            $instalmentId = $instalmentInfo['id'];


            // 查询支付单数据
            $payWhere = [
                'business_type'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                'business_no'    => $params['business_no'],
            ];
            $payInfo = \App\Order\Modules\Repository\Pay\PayCreater::getPayData($payWhere);
            if(empty($payInfo)){
                return false;
            }

            $_data = [
                'status'            => OrderInstalmentStatus::SUCCESS,
                'update_time'       => time(),
                'pay_type'          => 1,       //还款类型：0 代扣   1 主动还款
                'remark'            => '提前还款',
                'payment_amount'    => $payInfo['payment_amount'], //实际支付金额
            ];



            // 优惠券信息
            $recordData         = [];
            $counponWhere = [
                'business_type' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                'business_no'   => $params['business_no'],
            ];
            $counponInfo  = \App\Order\Modules\Repository\OrderCouponRepository::find($counponWhere);
            if(!empty($counponInfo)){
                // 修改优惠券使用状态
                $couponStatus = \App\Lib\Coupon\Coupon::useCoupon([$counponInfo['coupon_id']]);
                if($couponStatus != ApiStatus::CODE_0){
                    return apiResponse([],ApiStatus::CODE_50010);
                }

                $_data['payment_amount']    = $payInfo['payment_amount']; // 实际支付金额 元
                $_data['discount_amount']   = $instalmentInfo['amount'] - $payInfo['payment_amount'];

                $recordData['discount_type']                = $counponInfo['coupon_type'];
                $recordData['discount_value']               = $counponInfo['coupon_no'];
                $recordData['discount_name']                = "租金抵用券";
            }

            // 修改分期状态
            $result = \App\Order\Modules\Service\OrderGoodsInstalment::save(['trade_no'=>$params['business_no']],$_data);
            if(!$result){
                return false;
            }


            // 创建收支明细表
            $IncomeData = [
                'name'          => "商品-" . $instalmentInfo['goods_no'] . "分期" . $instalmentInfo['term'] . "代扣",
                'order_no'      => $instalmentInfo['order_no'],
                'business_type' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                'business_no'   => $params['business_no'],
                'appid'         => 1,
                'channel'       => \App\Order\Modules\Repository\Pay\Channel::Alipay,
                'out_trade_no'  => $params["payment_no"],
                'amount'        => $payInfo['payment_amount'],
                'create_time'   => time(),
            ];
            $IncomeId = \App\Order\Modules\Repository\OrderPayIncomeRepository::create($IncomeData);
            if( !$IncomeId ){
                \App\Lib\Common\LogApi::error('创建收支明细失败');
                return false;
            }


            // 创建扣款记录数据
            $recordData['instalment_id']        = $instalmentId;
            $recordData['status']               = OrderInstalmentStatus::SUCCESS;
            $recordData['create_time']          = time();
            $recordData['update_time']          = time();

            $record = \App\Order\Modules\Repository\OrderGoodsInstalmentRecordRepository::create($recordData);
            if(!$record){
               return false;
            }
            // 发送短信

            //发送短信通知 支付宝内部通知
            $notice = new \App\Order\Modules\Service\OrderNotice(
                \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                $instalmentId,
                "Repayment");
            $notice->notify();

            // 发送支付宝消息通知
            $notice->alipay_notify();


            // 提交蚁盾用户还款数据

            return true;

        }
    }

}