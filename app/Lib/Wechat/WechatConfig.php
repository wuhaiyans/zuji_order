<?php

namespace App\Lib\Wechat;


/**
 * 微信配置
 * 
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com>
 */
class WechatConfig{
	
	/**
	 * 应用ID
	 * @var string
	 */
	private $app_id = 'wx22df96cbe31ad9f7';
	
	/**
	 * 应用密钥
	 * @var string
	 */
	private $secret = 'b230d18c8dd69807d0e8ebcd4008ae05';
	
	/**
	 * 校验字符串
	 * @var string
	 */
	private $check_token = 'weixin_test_com';
	
	/**
	 * 构造函数
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array $config		配置参数
	 * [
	 *		'app_id' => '',	// 应用ID
	 *		'secret' => '',	// 应用密钥
	 * ]
	 */
	public function __construct( array $config=null ) {
		;
	}
	
	/**
	 * 
	 * @param string $app_id
	 * @return WechatConfig
	 */
	public function setAppId( string $app_id ):WechatConfig {
		$this->app_id = $app_id;
		return $this;
	}
	/**
	 * 应用ID
	 * @return string 
	 */
	public function getAppId():string {
		return $this->app_id;
	}
	
	/**
	 * 
	 * @param string $secret
	 * @return WechatConfig
	 */
	public function setSecret( string $secret ):WechatConfig {
		$this->secret = $secret;
		return $this;
	}
	/**
	 * 应用密钥
	 * @return string 
	 */
	public function getSecret():string {
		return $this->secret;
	}
	
	/**
	 * 
	 * @param string $token
	 * @return WechatConfig
	 */
	public function setCheckToken( string $token ):WechatConfig {
		$this->check_token = $token;
		return $this;
	}
	/**
	 * 
	 * @return string 
	 */
	public function getCheckToken():string {
		return $this->check_token;
	}
	
}
