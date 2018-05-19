<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 *
 * 统一退款接口
 * @author zjh
 */
class CommonRefundApi extends \App\Lib\BaseApi {

    /**
     * 退款申请接口
     * @param array $params
     * [
     *		'out_refund_no' => '', //业务系统退款码
     *		'payment_no'	=> '', //支付系统支付码
     *		'amount'		=> '', //支付金额
     *		'refund_back_url' => '', //退款回调URL
     * ]
     * @return mixed false：失败；array：成功
     * [
     * 		'out_refund_no'=>'', //订单系统退款码
     * 		'refund_no'=>'', //支付系统退款码
     * ]
     */
    public static function apply( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.payment.refund');
        $Response = $ApiRequest->setParams($params)->send();
        if( !$Response->isSuccessed() ){
			self::$error = $Response->getStatus()->getMsg();
            return false;
        }
		return $Response->getData();
    }
	
    /**
     * 退款查询接口
     * @param array $params
     * [
     *		'refund_no'		=> '', //支付系统退款码
     *		'out_refund_no' => '', //业务系统退款码
     * ]
     * @return mixed false：请求失败；array：成功
     * [
     *		'refund_no'		=> '', //支付系统退款码
     *		'out_refund_no' => '', //业务系统退款码
     *		'status'		=> '', //状态：success：交易成功；init：已初始化；processing：退款处理中
     * ]
     */
    public static function query( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_API'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.refund.query');
		
        $Response = $ApiRequest->setParams($params)->send();
		
        if( !$Response->isSuccessed() ){
			self::$error = $Response->getStatus()->getMsg();
            return false;
        }
		return $Response->getData();
    }
}