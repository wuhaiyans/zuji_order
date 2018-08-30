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

class ThirdAuthController extends Controller{

	
	/**
	 * 第三方授权url
	 * @param Request $request
	 * [
	 *		'channel'		=> '',	//【必须】授权渠道
	 *		'redirect_uri'	=> '',	//【必须】回跳地址
	 * ]
	 * @return type
	 */
	public function getUrl(Request $request){
		
        $all =$request->all();
		$params = $all['params'];
		
		// 参数校验
		if( !isset($params['channel']) || empty($params['channel'])
				|| !isset($params['redirect_uri']) || empty($params['redirect_uri']) ){
			LogApi::type('params-error')::error('授权参数错误',[
				'error' => 'channel 和 redirect_uri 必须',
				'params' => $params,
			]);
			return apiResponse([],ApiStatus::CODE_20001,'参数错误');
		}
		
		$url = '';
		
		// 微信授权
		if( $params['channel'] == 'WECHAT' ){
			try{
				$url = $this->_wechatUrl($params);
			} catch (\App\Lib\Wechat\WechatApiException $ex) {
				LogApi::type('exception')::error('微信授权接口错误',[
					'error' => $ex->getMessage(),
					'file' => $ex->getFile(),
					'line' => $ex->getLine(),
					'apiName' => $ex->getApiName(),
					'apiParams' => $ex->getApiParams(),
					'apiResult' => $ex->getErrdata(),
				]);
				return apiResponse([],ApiStatus::CODE_20001,'授权失败');
			}
		}else{
			LogApi::type('params-error')::error('授权渠道值错误',[
				'error' => 'channel不支持',
				'params' => $params,
			]);
			return apiResponse([],ApiStatus::CODE_20001,'参数错误');
		}
		
		return apiResponse([
			'url' => $url,
		],ApiStatus::CODE_0);
		
	}
	
	/**
	 * 第三方授权处理
	 * @param Request $request
	 * [
	 *		'channel'		=> '',	//【必须】授权渠道
	 *		'code'	=> '',	//【必须】回跳地址
	 *		'scope'	=> '',	//【可选】授权作用域
	 * ]
	 * @return type
	 */
	public function query(Request $request){
		
        $all =$request->all();
		$params = $all['params'];
		
		// 参数校验
		if( !isset($params['code']) || empty($params['code'])
				||  !isset($params['channel']) || empty($params['channel'])){
			LogApi::type('params-error')::error('授权查询参数错误',[
				'error' => 'code 和 channel 必须',
				'params' => $params,
			]);
			return apiResponse([],ApiStatus::CODE_20001,'参数错误');
		}
		
		$data = [];
		
		// 微信授权
		if( $params['channel'] == 'WECHAT' ){
			try{
				$user_info = $this->_wechatQuery($params);
				// 当前会话 微信openid缓存（用于微信JSAPI支付时使用）
				$_key = 'wechat_openid_'.$all['auth_token'];
				Redis::set($_key,$user_info['openid']);
				Redis::expire($_key, 60);
			} catch (\App\Lib\Wechat\WechatApiException $ex) {
				LogApi::type('exception')::error('微信授权接口错误',[
					'error' => $ex->getMessage(),
					'file' => $ex->getFile(),
					'line' => $ex->getLine(),
					'apiName' => $ex->getApiName(),
					'apiParams' => $ex->getApiParams(),
					'apiResult' => $ex->getErrdata(),
				]);
				return apiResponse([],ApiStatus::CODE_20001,'授权失败');
			}
			
		}else{
			LogApi::type('params-error')::error('授权渠道值错误',[
				'error' => 'channel不支持',
				'params' => $params,
			]);
			return apiResponse([],ApiStatus::CODE_20001,'参数错误');
		}
		
		return apiResponse($data,ApiStatus::CODE_0);
	}
	
	/**
	 * 获取微信授权地址
	 * @return string 授权url地址
	 */
	private function _wechatUrl( array $params ){
		
			$config = new \App\Lib\Wechat\WechatConfig( );
			$App = new \App\Lib\Wechat\WechatApp( $config );
			if( isset( $params['scope'] ) ){
				if( !in_array($params['scope'], ['snsapi_base','snsapi_userinfo']) ){
					LogApi::type('params-error')::error('授权查询参数错误',[
						'error' => 'scope错误',
						'params' => $params,
					]);
					throw new \Exception( '参数错误' );
				}
			}else{
				// 这里默认为 snsapi_userinfo 需要授权用户信息
				$params['scope'] = 'snsapi_userinfo';
			}
			return $App->getAuthUrl($params['redirect_uri'],$params['scope']);
			
	}
	
	/**
	 * 微信授权处理
	 * @return array
	 * [
	 *		'openid'	=> '',//【必选】
	 *		'nickname'	=> '',//【可选】
	 *		'sex'		=> '',//【可选】
	 *		'province'	=> '',//【可选】
	 *		'city'		=> '',//【可选】
	 *		'country'	=> '',//【可选】
	 *		'headimgurl'=> '',//【可选】
	 *		'unionid'	=> '',//【可选】
	 * ]
	 */
	private function _wechatQuery( array $params ){

		$config = new \App\Lib\Wechat\WechatConfig( );
		$App = new \App\Lib\Wechat\WechatApp( $config );
		$token_info = $App->getUserAccessToken( $params['code'] );
		$openid = $token_info['openid'];

		if( isset( $params['scope'] ) ){
			if( !in_array($params['scope'], ['snsapi_base','snsapi_userinfo']) ){
				LogApi::type('params-error')::error('授权查询参数错误',[
					'error' => 'scope错误',
					'params' => $params,
				]);
				throw new \Exception( '参数错误' );
			}
		}else{
			// 这里默认为 snsapi_base 不读取用户信息
			$params['scope'] = 'snsapi_base';
		}
		
		// 读取用户信息
		if( $params['scope'] == 'snsapi_userinfo' ){
			$third_user_info = $App->getUserInfo( $token_info['access_token'], $token_info['openid'] );
			return $third_user_info;	
		}
		
		return [
			'openid' => $openid,
		];
	}
	
	
}


