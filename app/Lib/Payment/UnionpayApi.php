<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 * 银联支付接口
 * @author zjh
 */
class UnionpayApi extends \App\Lib\BaseApi {
	
	/**
	 * 银联开通银行卡接口
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'acc_no' => '',// 银行卡号
	 *		'phone_no' => '',//	银行预留手机号
	 *		'certif_id' => '',//	持卡人身份证号
	 *		'customer_nm' => '',//	持卡人姓名
	 *		'user_id' => '',//	用户id
	 *		'front_url' => '',//	前端通知地址
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 *		'url' => '',
	 *		'params' => '',
	 * ]
	 */
	public static function openBankCard( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(config('paysystem.PAY_API'));
        $ApiRequest->setAppid( config('paysystem.PAY_APPID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.unionpay.open');
		$ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
			self::$error = '获取开通银行卡链接与参数失败';
            return false;
        }
		return $Response->getData();
	}

	/**
	 * 银联获取银行卡列表接口
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'user_id' => '',//用户id
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 *		'bankcardlist' => [
	 * 		'bankcard_id'=>'', //银行卡id
	 * 		'acc_no'=>'', //银行卡后四位
	 * 		'certif_id'=>'', //持卡人身份证号
	 * 		'customer_nm'=>'', //持卡人姓名
	 * 		'phone_no'=>'', //银行预留手机号
	 * 		'back_no'=>'', //银行简码
	 * 		'issIns_code_name'=>'', //银行名称
	 * 		'min_money'=>'', //最小支付金额
	 * 		'max_money'=>'', //最大支付金额
	 * ],
	 * ]
	 */
	public static function BankCardList( array $params ){
		$ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(config('paysystem.PAY_API'));
		$ApiRequest->setAppid( config('paysystem.PAY_APPID') );	// 业务应用ID
		$ApiRequest->setMethod('pay.unionpay.bankcardlist');
		$ApiRequest->setParams($params);
		$Response = $ApiRequest->send();

		if( !$Response->isSuccessed() ){
			self::$error = '获取银行卡列表失败';
			return false;
		}
		return $Response->getData();
	}

	/**
	 * 银联查询开通结果接口
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'acc_no' => '', //银行卡号
	 *		'user_id' => '', //用户id
	 * ]
	 * @return mixed false：失败；true：成功
	 */
	public static function backPolling( array $params ){
		$ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(config('paysystem.PAY_API'));
		$ApiRequest->setAppid( config('paysystem.PAY_APPID') );	// 业务应用ID
		$ApiRequest->setMethod('pay.unionpay.polling');
		$ApiRequest->setParams($params);
		$Response = $ApiRequest->send();

		if( !$Response->isSuccessed() ){
			self::$error = '开通银行卡失败';
			return false;
		}
		return true;
	}

	/**
	 * 银联发送验证码接口
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'bankcard_id' => '', //银行卡id
	 *		'out_no' => '', //订单系统支付码
	 *		'amount' => '', //支付金额
	 *		'user_id' => '', //用户id
	 *		'back_url' => '', //后端回调地址
	 *		'fenqi' => '', //分期期数 0 为不分期
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 * 		'status'=>'', //0发送成功 1发送失败
	 * ]
	 */
	public static function sendSms( array $params ){
		$ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(config('paysystem.PAY_API'));
		$ApiRequest->setAppid( config('paysystem.PAY_APPID') );	// 业务应用ID
		$ApiRequest->setMethod('pay.unionpay.smsconsume');
		$ApiRequest->setParams($params);
		$Response = $ApiRequest->send();

		if( !$Response->isSuccessed() ){
			self::$error = '发送验证码失败';
			return false;
		}
		return true;
	}

	/**
	 * 银联消费接口
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'bankcard_id' => '', //银行卡id
	 *		'out_no' => '', //订单系统支付码
	 *		'sms_code' => '', //验证码
	 *		'user_id' => '', //用户id
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 * 		'out_no'=>'', //订单系统支付码
	 * 		'payment_no'=>'', //支付系统支付码
	 * ]
	 */
	public static function consume( array $params ){
		$ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(config('paysystem.PAY_API'));
		$ApiRequest->setAppid( config('paysystem.PAY_APPID') );	// 业务应用ID
		$ApiRequest->setMethod('pay.unionpay.consume');
		$ApiRequest->setParams($params);
		$Response = $ApiRequest->send();

		if( !$Response->isSuccessed() ){
			self::$error = '发送验证码失败';
			return false;
		}
		return true;
	}

}
