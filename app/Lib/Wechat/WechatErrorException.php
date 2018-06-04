<?php

namespace App\Lib\Wechat;


/**
 * 微信错误异常
 * 微信错误基类，包含了 错误提示 和 具体数据
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class WechatErrorException extends \Exception{
	
	
	/**
	 * 错误提示
	 * @var string
	 */
	private $errmsg	= '';
	
	/**
	 * 错误数据
	 * @var array
	 */
	private $errdata	= [];
	
	/**
	 * 构造函数
	 * @param string $errmsg	错误提示
	 * @param array $errdata	错误数据
	 */
	public function __construct( string $errmsg='', array $errdata=[]) {
		$this->errmsg = $errmsg;
		$this->errdata = $errdata;
		parent::__construct('[微信]异常');
	}
	
	/**
	 * 读取 错题提示
	 * @return string
	 */
	function getErrmsg():string {
		return $this->errmsg;
	}

	/**
	 * 读取 错误数据
	 * @return array
	 */
	function getErrdata():array {
		return $this->errdata;
	}


}