<?php
namespace App\Lib\Common\Api;
use App\Lib\Curl;
/**
 * 
 * @author 
 */
class ApiClient {
	
	private $url = 'http://dev-order.zuji.com/common/test/testServer';
//	private $url = 'http://localhost/zuji/dev_common_service/api.php';
	
	/**
	 * 错误提示
	 * @var string
	 */
	private $error = '';
	
	/**
	 *
	 * @var ApiProtocol 
	 */
	private $protocol = null;
	
	
	public function __construct() {
		;
	}
	
	/**
	 * 获取错误提示
	 * @return string
	 */	
	public function getError(  ){
		return $this->error;
	}


	
	/**
	 * 设置协议对象
	 * @param ApiProtocol $protocol 协议对象
	 * @return ApiClient
	 */
	public function setProtocel( ApiProtocol $protocol ):ApiClient{
		$this->protocol = $protocol;
		return $this;
	}
	
	/**
	 * 请求接口
	 * @param ApiContext $apiContext	接口对象
	 * @return boolean
	 */
	public function request(ApiContext $apiContext){
		try {
			// 协议封装
			$data = $this->protocol->wrap( $apiContext );
			// 执行请求
			$output = Curl::post( $this->url, $data );
			if( Curl::hasError() ){
				$this->error = Curl::getError();
				return false;
			}
			// 协议封装
			$b = $this->protocol->unwrap( $apiContext, $output );
			if( !$b ){
				$this->error = $this->protocol->getError();
				return false;
			}
			return true;
		} catch (\Exception $exc) {
			$this->error = $exc->getMessage();
			return false;
		}

	}
	
	
	
}
