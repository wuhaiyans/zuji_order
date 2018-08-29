<?php

namespace App\Lib\Wechat;


/**
 * 微信应用
 */
class WechatApp{
	
	/**
	 * 应用配置
	 * @var WechatConfig 
	 */
	private $config;
	
	/**
	 * 应用存储
	 * @var WechatStorage 
	 */
	private $storage;
	
	/**
	 * 构造函数
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param WechatConfig $config		应用配置
	 * @param WechatStorage $storage	应用存储
	 */
	public function __construct(WechatConfig $config, WechatStorage $storage=null ) {
		$this->config = $config;
		$this->storage = new WechatStorage();
	}
	
	/**
	 * 获取授权url地址
	 * @param string $redirect_uri
	 * @param string $scope		授权作用域 取值返回：snsapi_base 或 snsapi_userinfo
	 * @return string
	 */
	public function getAuthUrl( string $redirect_uri, string $scope ){
		$redirect_uri = urlencode(urldecode($redirect_uri));
		return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->config->getAppId()
				.'&redirect_uri='.$redirect_uri
				.'&response_type=code&scope='.$scope.'&state=STATE#wechat_redirect';
	}
	
	/**
	 * 读取 用户授权token 
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param string $code 用户授权code
	 * @return array
	 * [
	 *		'access_token' => '',	// token
	 *		'refresh_token' => '',	// 刷新token
	 *		'expires_in' => '',		// 有效期，秒
	 *		'openid' => '',			// 用户 openid
	 * ]
	 * @throws WechatApiException
	 */
	public function getUserAccessToken( string $code ):array{
		$key = '__wechat_user_token_'.$code;
		// 读缓存
		if( ($data = $this->storage->get($key)) ){
			return $data['user_token'];
		}
		$user_token = WechatApi::getUserAccessToken($this->config->getAppId(), $this->config->getSecret(), $code);
		// 更新缓存
		$this->storage->set($key,
				['user_token' => $user_token],
				time()-10+$user_token['expires_in']);
		return $user_token;
	}
	
	/**
	 * 读取 用户授权信息 
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return array
	 * [
	 *		'openid'	=> '',		// 必须
	 *		'nickname'	=> '',	//
	 *		'sex'		=> '',//
	 *		'province'	=> '',//
	 *		'city'		=> '',//
	 *		'country'	=> '',//
	 *		'headimgurl'=> '',//
	 *		'unionid'	=> '',//【可选】
	 * ]
	 * @throws WechatApiException
	 */
	public function getUserInfo( string $access_token, string $openid ):array{
		$key = '__wechat_userinfo_'.$openid;
		// 读缓存
		if( ($data = $this->storage->get($key)) ){
			return $data;
		}
		$user_info = WechatApi::getUserInfo($access_token, $openid);
		// 更新缓存
		$this->storage->set($key,$user_info);
		return $user_info;
	}
	
	/**
	 * 读取接口Token
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return string
	 * @throws WechatApiException
	 */
	public function getApiAccessToken():string{
		$key = '__wechat_token_'.$this->config->getAppId();
		
		// 读缓存
		if( ($data = $this->storage->get($key)) ){
			return $data['access_token'];
		}
		$token_info = WechatApi::getApiAccessToken($this->config->getAppId(), $this->config->getSecret());
		// 更新缓存
		$this->storage->set($key,
				['access_token' => $token_info['access_token'],],
				time()-10+$token_info['expires_in']);
		
		return $token_info['access_token'];
	}
	
	/**
	 * 读取 JS API 的票据
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return string	票据
	 * @throws WechatApiException
	 */
	public function getJsapiTicket():string{
		$key = '__wechat_ticket_'.$this->config->getAppId();
		// 读缓存
		if( ($data = $this->storage->get($key)) ){
			return $data['ticket'];
		}
		$ticket_info = WechatApi::getJsapiTicket( $this->getApiAccessToken() );
		// 更新缓存
		$this->storage->set($key,
				['ticket' => $ticket_info['ticket'],],
				time()-10+$ticket_info['expires_in']);
		
		return $ticket_info['ticket'];
	}
	
	/**
	 * 生成 JS API 签名
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param string	$url
	 * @param int		$timestamp	时间戳
	 * @param string	$noncestr	随机字符串
	 * @return string 签名字符串
	 * @throws WechatApiException
	 */
	public function createJsapiSignature( string $url, int $timestamp, string $noncestr){
		$tmpArr = [
			'noncestr='.$noncestr,
			'jsapi_ticket='.$this->getJsapiTicket(),
			'timestamp='.$timestamp,
			'url='.$url
		];
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( '&',$tmpArr );
        return sha1( $tmpStr );
	}
	
	/**
	 * 校验 签名
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param string	$signature		待校验的签名字符串
	 * @param int		$timestamp		时间戳
	 * @param string	$noncestr		随机字符串
	 * @return bool	true：检验成功；false：校验失败
	 * @throws WechatApiException
	 */
	public function checkSignature( string $signature, int $timestamp, string $noncestr):bool {
		
		$tmpArr = [
			$this->config->getCheckToken(), 
			$timestamp, 
			$noncestr
		];

		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	public static function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}
	
	
}