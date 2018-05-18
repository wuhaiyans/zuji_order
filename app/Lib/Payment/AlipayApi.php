<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 * 
 *
 * @author 
 */
class AlipayApi extends \App\Lib\BaseApi {
	
	/**
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'out_no' => '',
	 *		'amount' => '',
	 *		'name' => '',
	 *		'back_url' => '',
	 *		'front_url' => '',
	 *		'fenqi' => '',
	 *		'user_id' => '',
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 *		'url' => '',
	 *		'params' => '',
	 * ]
	 */
	public static function getUrl( array $params ){
        $ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
		$ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.url');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
			self::$error = '获取支付链接错误';
            return false;
        }
		return $Response->getData();
	}

	/**
	 * 代扣 签约（获取签约地址）
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'user_id' => '', //租机平台用户ID
	 *		'out_agreement_no' => '', //业务平台签约协议号
	 *		'front_url' => '', //前端回跳地址
	 *		'back_url' => '', //后台通知地址
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 *		'withholding_url' => '',//签约跳转url地址
	 * ]
	 */
	public static function withholdingUrl( array $params ){
		$ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
		$ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
		$ApiRequest->setMethod('pay.alipay.withholdingurl');
		$ApiRequest->setParams($params);
		$Response = $ApiRequest->send();
		if( !$Response->isSuccessed() ){
			self::$error = '支付宝代扣 获取url地址接口';
			return false;
		}
		return $Response->getData();
	}

	/**
	 * 预授权获取URL接口
	 * @param string $appid		应用ID
	 * @param array $params
	 * [
	 *		'out_auth_no' => '', //订单系统授权码
	 *		'amount' => '', //授权金额；单位：分
	 *		'front_url' => '', //前端回跳地址
	 *		'back_url' => '', //后台通知地址
	 *		'name' => '', //预授权名称
	 *		'user_id' => '', //用户ID
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 *		'Authorization_url' => '',跳转预授权接口
	 * ]
	 */
	public static function fundAuthUrl( array $params ){
		$ApiRequest = new ApiRequest();
		$ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
		$ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
		$ApiRequest->setMethod('pay.alipay.fundauthurl');
		$ApiRequest->setParams($params);
		$Response = $ApiRequest->send();
		if( !$Response->isSuccessed() ){
			self::$error = '获取预授权链接地址失败';
			return false;
		}
		return $Response->getData();
	}
}
