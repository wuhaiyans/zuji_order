<?php
namespace App\Lib\Common\Api;

/**
 * ApiProtocol 接口协议
 *
 * @author 
 */
interface ApiProtocol {
	
	
	/**
	 * 获取错误提示
	 * @return string
	 */	
	public function getError(  ):string;
	/**
	 * 
	 * @param string $pubk		远端公钥字符串
	 * @return ApiProtocol
	 */
	public function setRemotePublicKey(string $pubk):ApiProtocol;
	
	/**
	 * 
	 * @param string $prik		本地私钥字符串
	 * @return ApiProtocol
	 */
	public function setLocalPrivateKey(string $prik):ApiProtocol;
	
	/**
	 * 协议封装
	 * @param ApiContext $apiContext
	 * @return string
	 * @throws Exception
	 */
	public function wrap( ApiContext $apiContext ):string;
	
	/**
	 * 协议解析
	 * @param ApiContext $apiContext
	 * @param string $output
	 * @return bool
	 * @throws Exception
	 */
	public function unwrap( ApiContext $apiContext, string $output ):bool;
	
	
}
