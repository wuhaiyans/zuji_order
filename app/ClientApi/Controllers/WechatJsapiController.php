<?php
/**
 *      author: heaven
 *      验证client，token信息,请求真实api地址信息，并返回数据
 *      date: 2018-06-08
 */
namespace App\ClientApi\Controllers;
use Illuminate\Http\Request;

use App\Lib\Common\LogApi;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Redis;

/**
 * 微信 JSAPI 接口
 */
class WechatJsapiController extends Controller{

	
	/**
	 * 生成 JSAPI 签名
	 * @param Request $request
	 * [
	 *		'url'		=> '',	//【必须】JS页面地址
	 * ]
	 * @return array
	 * [
	 *		'appId' => '',
	 *		'timestamp' => '',
	 *		'nonceStr' => '',
	 *		'signature' => '',
	 * ]
	 */
	public function sign(Request $request){
		
        $all =$request->all();
		$params = $all['params'];
		
		// 参数校验
		if( !isset($params['url']) || empty($params['url']) ){
			LogApi::type('params-error')::error('微信JS签名参数错误',[
				'error' => 'url 必须',
				'params' => $params,
			]);
			return apiResponse([],ApiStatus::CODE_20001,'参数错误');
		}
		
		$config = new \App\Lib\Wechat\WechatConfig( );
		$App = new \App\Lib\Wechat\WechatApp( $config );
		
		$data = [
			'appId' => $config->getAppId(),
			'timestamp' => time(),
			'nonceStr' => $App->getNonceStr(),
		];
		try{
			//JS页面签名
			$data['signature'] = $App->createJsapiSignature($params['url'],$data['timestamp'],$data['nonceStr']);
			//
			return apiResponse($data,ApiStatus::CODE_0);
		} catch (\App\Lib\Wechat\WechatApiException $ex) {
			LogApi::type('exception')::error('微信JSAPI签名接口错误',[
				'error' => $ex->getMessage(),
				'file' => $ex->getFile(),
				'line' => $ex->getLine(),
				'apiName' => $ex->getApiName(),
				'apiParams' => $ex->getApiParams(),
				'apiResult' => $ex->getErrdata(),
			]);
			return apiResponse([],ApiStatus::CODE_50000,'微信JSAPI签名失败');
		}
		
	}
	
	
}


