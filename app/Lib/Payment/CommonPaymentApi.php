<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 * 公共支付接口
 *
 * @author 
 */
class CommonPaymentApi extends \App\Lib\BaseApi {
	
	/**
	 * 统一支付页面URL接口
	 * @param array $params
	 * [
	 *		'out_payment_no'	=> '',	//【必选】string 业务支付唯一编号
	 *		'payment_channel'	=> '',	//【必选】int 支付渠道
	 *		'payment_amount'	=> '',	//【必选】int 交易金额；单位：分
	 *		'payment_fenqi'		=> '',	//【必选】int 分期数
	 *		'name'			=> '',	//【必选】string 交易名称
	 *		'back_url'		=> '',	//【必选】string 后台通知地址
	 *		'front_url'		=> '',	//【必选】string 前端回跳地址
	 *		'user_id'		=> '',	//【可选】int 业务平台yonghID
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 *		'payment_url' => '',//支付链接
	 * ]
	 */
	public static function pageUrl( array $params ){
        $ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(env('PAY_SYSTEM_API'));
		$ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.payment.page.url');
		
        $Response = $ApiRequest->setParams( $params )->send();
		
        if( !$Response->isSuccessed() ){
			self::$error = $Response->getStatus()->getMsg();
            return false;
        }
		return $Response->getData();
	}

	/**
	 * 统一支付查询接口
	 * @param array $params
	 * [
	 *		'refund_no'		=> '',	//【必选】string 支付系统退款编号
	 *		'out_refund_no'	=> '',	//【必选】string 业务系统退款编号
	 * ]
	 * @return mixed false：接口请求失败；array：支付信息
	 * [
	 *		'refund_no'		=> '',	//【必选】string 支付系统退款编号
	 *		'out_refund_no'	=> '',	//【必选】string 业务系统退款编号
	 *		'status'			=> '',	//【必选】string success：支付成功；其他值为未完成支付
	 * ]
	 */
	public static function query( array $params ){
        $ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(env('PAY_SYSTEM_API'));
		$ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.payment.query');
		
        $Response = $ApiRequest->setParams( $params )->send();
		
        if( !$Response->isSuccessed() ){
			self::$error = $Response->getStatus()->getMsg();
            return false;
        }
		return $Response->getData();
	}

}
