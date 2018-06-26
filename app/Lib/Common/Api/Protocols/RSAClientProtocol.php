<?php
namespace App\Lib\Common\Api\Protocols;
use App\Lib\Common\Api\ApiProtocol;
use App\Lib\Common\Api\ApiContext;
use App\Lib\RSA;

/**
 * RSA 接口协议
 *
 * @author 
 */
class RSAClientProtocol  implements ApiProtocol {
	
	/**
	 * 错误提示
	 * @var string
	 */
	private $error = '';
	
	private $_remotePublicKey = '';
	private $_remotePublicKeyResource = '';
	private $_localPrivateKey = '';
	private $_localPrivateKeyResource = '';
	
	public function __construct() {}
	
	/**
	 * 获取错误提示
	 * @return string
	 */	
	public function getError(  ):string{
		return $this->error;
	}
	/**
	 * 
	 * @param string $pubk		远端公钥字符串
	 * @return ApiProtocol
	 */
	public function setRemotePublicKey(string $pubk):ApiProtocol{
		$this->_remotePublicKey = $pubk;
		$this->_remotePublicKeyResource = RSA::getPublicKeyFromX509($pubk);
		return $this;
	}
	
	/**
	 * 
	 * @param string $prik		本地私钥字符串
	 * @return ApiProtocol
	 */
	public function setLocalPrivateKey(string $prik):ApiProtocol{
		$this->_localPrivateKey = $prik;
		$this->_localPrivateKeyResource = RSA::getPrivateKeyFromX509($prik);
		return $this;
	}
	
	/**
	 * 协议封装
	 * @param ApiContext $apiContext
	 * @return string
	 * @throws Exception
	 */
	public function wrap( ApiContext $apiContext ):string{
		
		$params = json_encode($apiContext->getRequestParams());
		if( !$params ){
			throw new \Exception('请求协议封装错误');
		}
		
		$params = RSA::encodeData($params, $this->_remotePublicKeyResource);
		$sign = RSA::sign($params, $this->_localPrivateKeyResource);
		return json_encode([
			'appid' => '1',
			'method' => $apiContext->getMethod(),
			'timestamp' => date('Y-m-d H:i:s'),
			'version' => '1.0',
			'auth_token' => '',
			'params' => $params,
			'sign_type' => 'RSA',
			'sign' => $sign,
		]);
	}
	
	/**
	 * 协议解析
	 * @param ApiContext $apiContext
	 * @param string $output
	 * @return bool
	 * @throws Exception
	 */
	public function unwrap( ApiContext $apiContext, string $output ):bool{
		$res = json_decode($output,true);
		if( !$res ){
			throw new \Exception('[协议错误]协议格式错误');
		}
//		if( !isset($res['appid']) ){
//			throw new \Exception('[协议错误][appid]域不存在');
//		}
//		if( !isset($res['method']) ){
//			throw new \Exception('[协议错误][method]域不存在');
//		}
//		if( !isset($res['version']) ){
//			throw new \Exception('[协议错误][version]域不存在');
//		}
//		if( !isset($res['auth_token']) ){
//			throw new \Exception('[协议错误][auth_token]域不存在');
//		}
//		if( !isset($res['sign_type']) ){
//			throw new \Exception('[协议错误][sign_type]域不存在');
//		}
//		if( !isset($res['sign']) ){
//			throw new \Exception('[协议错误][sign]域不存在');
//		}
		
		if( !isset($res['code']) ){
			throw new \Exception('[协议错误][code]域不存在');
		}
		if( !isset($res['msg']) ){
			throw new \Exception('[协议错误][msg]域不存在');
		}
		if( !isset($res['sub_code']) ){
			throw new \Exception('[协议错误][sub_code]域不存在');
		}
		if( !isset($res['sub_msg']) ){
			throw new \Exception('[协议错误][sub_msg]域不存在');
		}
		if( !isset($res['data']) ){
			throw new \Exception('[协议错误]data域不存在');
		}
		
		// 请求处理状态
		if( $res['code'] != '0' ){
			$this->error = $res['msg']?$res['msg']:'--服务器未知错误--';
			return false;
		}
		
		if( $res['sign_type'] == 'RSA' ){
			// 服务端公钥验签
			$b = RSA::verify($res['data'], $res['sign'], $this->_remotePublicKeyResource);
			if( !$b ){
				$this->error = '数据验签失败';
				return false;
			}
			// 客户端私钥解密
			$res['data'] = RSA::decodeData($res['data'], $this->_localPrivateKeyResource);
			if( !$res['data'] ){
				$this->error = '数据解密失败';
				return false;
			}
			$res['data'] = json_decode($res['data'],true);
		}
		
		// 响应参数写入接口对象
		$b = $apiContext->setResponseData( $res );
		if( !$b ){
			$this->error = $apiContext->getError();
			return false;
		}
		return true;
	}
	
	
}
