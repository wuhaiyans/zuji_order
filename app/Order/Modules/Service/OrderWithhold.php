<?php
namespace App\Order\Modules\Service;

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
        //开启事务
        DB::beginTransaction();
        // 查询分期信息
        $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            DB::rollBack();
            // 提交事务
            return false;
        }

        // 订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            \App\Lib\Common\LogApi::error('查询订单错误');
            return false;
        }
        if($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService){
            DB::rollBack();
            \App\Lib\Common\LogApi::error('订单状态不在服务中');
            return false;
        }

        //判断是否允许扣款
        $allow = OrderGoodsInstalment::allowWithhold($instalmentId);
        if(!$allow){
            DB::rollBack();
            \App\Lib\Common\LogApi::error('不允许扣款');
            return false;
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderGoodsInstalment::save(['id'=>$instalmentId],$data);
        if(!$result){
            DB::rollBack();
            \App\Lib\Common\LogApi::error('扣款备注保存失败');
            return false;
        }
        // 商品
        $subject = '订单-'.$instalmentInfo['order_no'].'-'.$instalmentInfo['goods_no'].'-第'.$instalmentInfo['times'].'期扣款';

        // 价格
        $amount = $instalmentInfo['amount'] * 100;
        if( $amount<0 ){
            DB::rollBack();
            \App\Lib\Common\LogApi::error('扣款金额不能小于1分');
            return false;
        }

        //判断支付方式
        if( $orderInfo['pay_type'] == PayInc::MiniAlipay ){
            //获取订单的芝麻订单编号
            $miniOrderInfo = \App\Order\Modules\Repository\MiniOrderRentNotifyRepository::getMiniOrderRentNotify( $instalmentInfo['order_no'] );
            if( empty($miniOrderInfo) ){
                \App\Lib\Common\LogApi::info('本地小程序确认订单回调记录查询失败',$orderInfo['order_no']);
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
                \App\Lib\Common\LogApi::error('小程序扣款操作成功');
                return false;
            }elseif($pay_status =='PAY_FAILED'){
                OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term']);
                \App\Lib\Common\LogApi::error('小程序扣款请求失败');
                return false;
            }elseif($pay_status == 'PAY_INPROGRESS'){
                \App\Lib\Common\LogApi::error('小程序扣款处理中请等待');
                return false;
            }else{
                \App\Lib\Common\LogApi::error('小程序扣款处理失败（内部失败）');
                return false;
            }
        }else {
            // 代扣协议编号
            $channel = \App\Order\Modules\Repository\Pay\Channel::Alipay;   //暂时保留
            // 查询用户协议
            $withhold = WithholdQuery::getByUserChannel($instalmentInfo['user_id'], $channel);

            $withholdInfo = $withhold->getData();

            $agreementNo = $withholdInfo['out_withhold_no'];
            if (!$agreementNo) {
                DB::rollBack();
                \App\Lib\Common\LogApi::error('用户代扣协议编号错误');
                return false;
            }
            // 代扣接口
            $withholding = new \App\Lib\Payment\CommonWithholdingApi;

            $backUrl = config('app.url') . "/order/pay/createpayNotify";

            $withholding_data = [
                'out_trade_no'  => $instalmentInfo['id'], //业务系统业务吗
                'amount'        => $amount,              //交易金额；单位：分
                'back_url'      => $backUrl,             //后台通知地址
                'name'          => $subject,             //交易备注
                'agreement_no'  => $agreementNo,         //支付平台代扣协议号
                'user_id'       => $orderInfo['user_id'],//业务平台用户id
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
                    \App\Lib\Common\LogApi::error('买家余额不足');
                    return false;
                }
            }

            // 创建扣款记录
            $instalmentRecord = [
                'instalment_id'             => $instalmentId,   // 分期ID
                'type'                      => 1,               // 类型 1：代扣；2：主动还款
                'payment_amount'            => $amount,         // 实际支付金额
                'status'                    => OrderInstalmentStatus::PAYING, // 状态：
                'create_time'               => time(),          // 创建时间
            ];
            $record = \App\Order\Modules\Repository\OrderGoodsInstalmentRecordRepository::create($instalmentRecord);
            if(!$record){
                DB::rollBack();
                \App\Lib\Common\LogApi::error('创建扣款记录失败');
            }

            //发送短信通知 支付宝内部通知
            $notice = new \App\Order\Modules\Service\OrderNotice(
                OrderStatus::BUSINESS_FENQI,
                $instalmentId,
                "InstalmentWithhold");
            $notice->notify();

            // 发送支付宝消息通知
            $notice->alipay_notify();

        }
        // 提交事务
        DB::commit();
        return true;
    }



    /**
     * 提前还款异步回调接口
     * @requwet Array
     * [
     *       "payment_no":"mock",            //类型：String  必有字段  备注：支付平台支付码
     *       "out_no":"mock",                //类型：String  必有字段  备注：订单平台支付码
     *       "status":"mock",                //类型：String  必有字段  备注：init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
     *       "reason":"mock"                 //类型：String  必有字段  备注：失败理由，成功无此字段
     * ]
     * @return String FAIL：失败  SUCCESS：成功
     */
    public function repaymentNotify($params){
        $params = filter_array($params, [
            'payment_no'    => 'required',
            'out_no'        => 'required',
            'status'        => 'required',
            'reason'        => 'required',
        ]);

        if(count($params) < 4){
            echo "FAIL";exit;
        }


        $status = [
            'init'          => \App\Order\Modules\Inc\OrderInstalmentStatus::UNPAID,
            'success'       => \App\Order\Modules\Inc\OrderInstalmentStatus::SUCCESS,
            'failed'        => \App\Order\Modules\Inc\OrderInstalmentStatus::FAIL,
            'finished'      => \App\Order\Modules\Inc\OrderInstalmentStatus::CANCEL,
            'closed'        => \App\Order\Modules\Inc\OrderInstalmentStatus::CANCEL,
            'processing'    => \App\Order\Modules\Inc\OrderInstalmentStatus::PAYING,
        ];

        //开启事务
        DB::beginTransaction();
        // 查询分期信息
        $instalmentInfo = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['id'=>$params['out_no']]);
        if( !is_array($instalmentInfo)){
            // 提交事务
            DB::rollBack();
            echo "FAIL";exit;
        }
        $instalmentId = $instalmentInfo['id'];

        if(!isset($status[$params['status']])){
            DB::rollBack();
            echo "FAIL";exit;
        }


        $_data = [
            'status'            => $status[$params['status']],
            'update_time'       => time(),
            'pay_type'          => 1,       //还款类型：0 代扣   1 主动还款
            'remark'            => '提前还款',
            'payment_amount'    => $instalmentInfo['amount'], //实际支付金额
        ];


        // 查询支付单
        $payWhere = [
            'businessType'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
            'businessNo'    => $params['out_no'],
        ];
        $PayData = \App\Order\Modules\Repository\Pay\PayCreater::getPayData($payWhere);
        if(empty($PayData)){
            DB::rollBack();
            echo "FAIL";exit;
        }


        // 优惠券信息
        $counponWhere = [
            'business_type' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
            'business_no'   => $params['out_no'],
        ];
        $counponInfo  = \App\Order\Modules\Repository\OrderCouponRepository::find($counponWhere);
        if(!empty($counponInfo)){
            $_data['payment_amount']    = $PayData['payment_amount']; // 实际支付金额 元
            $_data['discount_amount']   = $instalmentInfo['amount'] - $PayData['payment_amount'];
        }

        // 修改分期状态
        $result = \App\Order\Modules\Service\OrderGoodsInstalment::save(['id'=>$params['out_no']],$_data);
        if(!$result){
            DB::rollBack();
            echo "FAIL";exit;
        }

        // 还原租金优惠券
        if($params['status'] == "failed"){

            if(!empty($counponInfo)){
                // 修改优惠券状态
                $couponStatus = \App\Lib\Coupon\Coupon::setCoupon(['user_id'=>$instalmentInfo['user_id'],'coupon_id'=>$counponInfo['coupon_id']]);
                if($couponStatus != ApiStatus::CODE_0){
                    DB::rollBack();
                    echo "FAIL";exit;
                }
            }
        }

        // 创建收支明细表
        $IncomeData = [
            'name'          => "商品-" . $instalmentInfo['goods_no'] . "分期" . $instalmentInfo['term'] . "代扣",
            'order_no'      => $instalmentInfo['order_no'],
            'business_type' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
            'business_no'   => $instalmentId,
            'appid'         => 1,
            'channel'       => \App\Order\Modules\Repository\Pay\Channel::Alipay,
            'out_trade_no'  => $params["payment_no"],
            'amount'        => $PayData['payment_amount'],
            'create_time'   => time(),
        ];
        $IncomeId = \App\Order\Modules\Repository\OrderPayIncomeRepository::create($IncomeData);
        if( !$IncomeId ){
            DB::rollBack();
            \App\Lib\Common\LogApi::error('创建收支明细失败');
            return apiResponse([], ApiStatus::CODE_50000, '创建扣款记录失败');
        }

        // 修改扣款记录数据
        $recordData = [
            'status'        => $status[$params['status']],
            'update_time'   => time(),
        ];
        $record = \App\Order\Modules\Repository\OrderGoodsInstalmentRecordRepository::save(['instalment_id'=>$params['out_no']],$recordData);
        if(!$record){
            DB::rollBack();
            echo "FAIL";exit;
        }
        // 提交事务
        DB::commit();

        // 发送短信

        //发送短信通知 支付宝内部通知
        $notice = new \App\Order\Modules\Service\OrderNotice(
            OrderStatus::BUSINESS_FENQI,
            $instalmentId,
            "Repayment");
        $notice->notify();

        // 发送支付宝消息通知
        $notice->alipay_notify();


        // 提交蚁盾用户还款数据


        echo "SUCCESS";
    }

}