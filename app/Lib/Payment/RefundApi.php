<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 *
 * 退款接口
 * @author zjh
 */
class RefundApi extends \App\Lib\BaseApi {

    /**
     * 退款接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'out_refund_no' => '', //订单系统退款码
     *		'payment_no' => '', //支付系统支付码
     *		'amount' => '', //支付金额
     *		'refund_back_url' => '', //退款回调URL
     * ]
     * @return mixed false：失败；array：成功
     * [
     * 		'out_refund_no'=>'', //订单系统退款码
     * 		'refund_no'=>'', //支付系统退款码
     * ]
     */
    public static function refund( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.payment.refund');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '退款请求失败';
            return false;
        }
        return true;
    }
}