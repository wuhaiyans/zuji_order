<?php
namespace App\Lib\Common\Api;
/**
 * 
 *
 * @author Administrator
 */
abstract class ApiContext {
	
	private $data = [];
	
	/**
	 * 
	 * @var string
	 */
	protected $error = '';
	
	
	/**
	 * 获取接口错误提示
	 * @return string 
	 */
	public function getError():string{
		return $this->error;
	}

	/**
	 * 获取请求接口名称
	 * @return string
	 */
	abstract public function getMethod():string;

	/**
	 * 获取请求参数
	 * @return array
	 */
	abstract public function getRequestParams():array;
	
	/**
	 * 设置响应参数
	 * @param array $data 响应参数集合
	 * @return bool
	 */
	public function setResponseData(array $data):bool{
		$this->data = $data;
		return true;
	}
	
	/**
	 * 获取响应参数
	 * @return array
	 */
	public function getResponseData():array{
		return $this->data;
	}
}
