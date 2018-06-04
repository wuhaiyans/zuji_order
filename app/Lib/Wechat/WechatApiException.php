<?php

namespace App\Lib\Wechat;


/**
 * 微信接口异常
 * 接口名称和接口参数
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class WechatApiException extends WechatErrorException{
	
	/**
	 * 接口名称
	 * @var string
	 */
	private $api_name	= '';
	
	/**
	 * 接口请求参数
	 * @var array
	 */
	private $api_params	= [];
	
	
	/**
	 * 构造函数
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param string	$api_name	接口名称
	 * @param array		$api_params	接口请求参数
	 * @param string	$errmsg		错误提示
	 * @param array		$result		错误结果
	 */
	public function __construct( string $api_name, array $api_params, string $errmsg='', array $result=[]) {
		$this->api_name = $api_name;
		$this->api_params = $api_params;
		parent::__construct($errmsg, $result);
	}
	
	/**
	 * 接口名称
	 * @return string
	 */
	public function getApiName():string{
		return $this->api_name;
	}
	
	/**
	 * 接口请求参数
	 * @return array
	 */
	public function getApiParams():array{
		return $this->api_params;
	}
	
}