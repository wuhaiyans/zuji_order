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
	 * @var WechatConfig 
	 */
	private $storage;
	
	/**
	 * 构造函数
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param WechatConfig $config		应用配置
	 * @param WechatStorage $storage	应用存储
	 */
	public function __construct( WechatConfig $config, WechatStorage $storage=null ) {
		$this->config = $config;
		$this->storage = new WechatStorage();
	}
	
	
	/**
	 * 读取接口Token
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return string
	 * @throws WechatApiException
	 */
	public function getAccessToken():string{
		$key = '__wechat_token_'.$this->config->getAppId();
		// 读缓存
		if( ($data = $this->storage->get($key)) ){
			return $data['access_token'];
		}
		$token_info = WechatApi::getAccessToken($this->config->getAppId(), $this->config->getSecret());
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
		$ticket_info = WechatApi::getJsapiTicket( $this->getAccessToken() );
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
	
	
	
}