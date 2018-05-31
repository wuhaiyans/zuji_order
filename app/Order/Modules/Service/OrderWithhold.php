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
        $instalmentInfo = OrderInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            DB::rollBack();
            // 提交事务
            return false;
        }

        // 状态在支付中或已支付时，直接返回成功
        if( $instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS && $instalmentInfo['status'] = OrderInstalmentStatus::PAYING ){
            return true;
        }

        // 扣款交易码
        if( $instalmentInfo['trade_no'] == '' ){
            // 1)记录租机交易码
            $b = OrderInstalment::set_trade_no($instalmentId, createNo());
            if( $b === false ){
                DB::rollBack();
                return false;
            }

        }

        $tradeNo = $instalmentInfo['trade_no'];

        // 订单
        //查询订单记录
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            return false;
        }

        // 查询用户协议
        $withholdInfo = OrderPayWithhold::find($orderInfo['user_id']);
        if(empty($withholdInfo)){
            DB::rollBack();
            return false;
        }

        $userInfo = \App\Lib\User\User::getUser($orderInfo['user_id']);
        if( !is_array($userInfo )){
            DB::rollBack();
            return false;
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderInstalmentRepository::save(['id'=>$instalmentId],$data);
        if(!$result){
            DB::rollBack();
            return false;
        }
        // 商品
        $subject = '订单-'.$instalmentInfo['order_no'].'-'.$instalmentInfo['goods_no'].'-第'.$instalmentInfo['times'].'期扣款';

        // 价格 元转化分
        $amount = $instalmentInfo['amount'] * 100;
        if( $amount < 0 ){
            DB::rollBack();
            return false;
        }


        $orderGoods = New \App\Order\Modules\Service\OrderGoods();
        $goodsInfo  = $orderGoods->getGoodsInfo($instalmentInfo['goods_no']);
        if(!$goodsInfo){
            return false;
        }
        //扣款要发送的短信
        $dataSms =[
            'mobile'        => $userInfo['mobile'],
            'orderNo'       => $orderInfo['order_no'],
            'realName'      => $userInfo['realname'],
            'goodsName'     => $goodsInfo['goods_name'],
            'zuJin'         => $amount,
        ];



        //判断支付方式
        if( $orderInfo['pay_type'] == \App\Order\Modules\Inc\PayInc::MiniAlipay ){
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
            $miniParams['out_trans_no']     = $tradeNo;
            $miniParams['pay_amount']       = $amount;
            $miniParams['remark']           = $subject;
            $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );
            //判断请求发送是否成功
            if($pay_status == 'PAY_SUCCESS'){
                return true;
            }elseif($pay_status =='PAY_FAILED'){
                OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                return false;
            }elseif($pay_status == 'PAY_INPROGRESS'){
                return false;
            }else{
                return false;
            }
        }else {
            // 支付宝用户的user_id
            $alipayUserId = $withholdInfo['out_withhold_no'];
            if (!$alipayUserId) {
                DB::rollBack();
                return false;
            }

            // 代扣协议编号
            $agreementNo = $withholdInfo['withhold_no'];
            if (!$agreementNo) {
                DB::rollBack();
                return false;
            }
            // 代扣接口
            $withholding = new \App\Lib\Payment\CommonWithholdingApi;

            $backUrl = env("API_INNER_URL") . "/createpayNotify";

            $withholding_data = [
                'out_trade_no'  => $instalmentInfo['trade_no'], //业务系统授权码
                'amount'        => $amount,                     //交易金额；单位：分
                'back_url'      => $backUrl,                    //后台通知地址
                'name'          => $subject,                    //交易备注
                'agreement_no'  => $agreementNo,                //支付平台代扣协议号
                'user_id'       => $orderInfo['user_id'],       //业务平台用户id
            ];

            try{
                // 请求代扣接口
                $withholding->deduct($withholding_data);
            }catch(\Exception $exc){
                DB::rollBack();
                \App\Lib\Common\LogApi::error('分期代扣错误', $withholding_data);
                //捕获异常 买家余额不足
                if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                    return false;
                } else {
                    return false;
                }
            }

            //发送手机短信
            $business_type = \App\Order\Modules\Repository\ShortMessage\Config::CHANNELID_OFFICAL;
            $orderNoticeObj  = new \App\Order\Modules\Service\OrderNotice($business_type, "", "InstalmentWithhold");
            $orderNoticeObj->notify($dataSms);

            //发送消息通知
            //通过用户id查询支付宝用户id
            $MessageSingleSendWord = new \App\Lib\AlipaySdk\sdk\MessageSingleSendWord($alipayUserId);
            //查询账单
            $year = substr($instalmentInfo['term'], 0, 4);
            $month = substr($instalmentInfo['term'], -2);
            $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
            $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
            $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
            $messageArr = [
                'amount' => $amount,
                'bill_type' => '租金',
                'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
                'pay_time' => date('Y-m-d H:i:s'),
            ];
            $b = $MessageSingleSendWord->PaySuccess($messageArr);
            if ($b === false) {
                Log::error("发送消息通知错误-" . $MessageSingleSendWord->getError());
            }
        }

        DB::commit();
        return true;
    }



    /**
     * 主动还款回调
     * @requwet Array
     * [
     *      'reason'            => '', 【必须】 String 错误原因
     *      'status'            => '', 【必须】 int：success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
     *      'payment_no'        => '', 【必须】 String 支付平台支付码
     *      'out_no'            => '', 【必须】 String 订单平台支付码
     * ]
     * @return String FAIL：失败  SUCCESS：成功
     */
    public function repaymentNotify($params){

        $rules = [
            'payment_no'  => 'required',
            'out_no'      => 'required',
            'status'      => 'required',
            'reason'      => 'required',
        ];
        $validator = app('validator')->make($params, $rules);
        if ($validator->fails()) {
            set_apistatus(ApiStatus::CODE_20001, $validator->errors()->first());
            return false;
        }

        $tradeNo = $params['out_no'];

        if($params['status'] == "success"){
            //修改分期状态
            $b = OrderInstalment::save(['trade_no'=>$tradeNo],['status'=>OrderInstalmentStatus::SUCCESS]);
            if(!$b){
                echo "FAIL";exit;
            }
        }else{
            // 支付失败 恢复优惠券
            $where = [
                'business_type'     => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                'business_no'       => $tradeNo,
            ];
            $couponInfo = \App\Order\Modules\Repository\OrderCouponRepository::find($where);
            if(!empty($couponInfo)){
                $instalmentInfo     = $this->queryInfo(['trade_no'=>$tradeNo]);
                $arr = [
                    'user_id'       => $instalmentInfo['user_id'],
                    'coupon_id'     => $couponInfo['coupon_id'],
                ];
                \App\Lib\Coupon\Coupon::setCoupon($arr);

            }

            LogApi::info('支付异步通知', $params);
        }

        echo "SUCCESS";

    }

}