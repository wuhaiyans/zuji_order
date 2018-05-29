<?php
namespace App\Lib;

/**
 * 接口异常类
 */
class ApiException extends \Exception {
	
	private $response;
	
	public function __construct( ApiResponse $response ) {
		$this->response = $response;
		parent::__construct($response->getStatus()->getMsg(), $response->getStatus()->getCode());
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getData(){
		return $this->response->getData();
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getOriginalValue(){
		return $this->response->getOriginalValue();
	}
	
}
