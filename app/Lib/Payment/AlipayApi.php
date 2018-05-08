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
 * ]
 * @return mixed false：失败；array：成功
 * [
 *		'url' => '',
 *		'params' => '',
 * ]
 */
	public static function getUrl( array $params ){


		$ApiRequest = new ApiRequest();
		$ApiRequest->setUrl('https://dev-pay-zuji.huishoubao.com/api');
//        $ApiRequest->setUrl('https://localhost/zuji/dev-PayService/public/index.php/alipay/Test/getUrl');
		$ApiRequest->setAppid('1');	// 业务应用ID
		$ApiRequest->setMethod('pay.alipay.url');
		$ApiRequest->setParams([
			'out_no' => time(),	// 业务系统支付编号
			'amount' => '1',	// 金额，单位：分
			'name' => '测试商品支付',// 支付名称
			'back_url' => 'https://alipay/Test/notify',
			'front_url' => 'https://alipay/Test/front',
			'fenqi' => 0,	// 分期数
			'user_id' => 5,// 用户ID
		]);
		$Response = $ApiRequest->send();

		if( !$Response->isSuccessed() ){
			self::$error = '获取支付链接错误';
			return false;
		}
		return $Response->getData();
	}
}
