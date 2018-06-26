<?php
namespace App\Lib\Common\Api\Protocols;
use App\Lib\Common\Api\ApiProtocol;
use App\Lib\Common\Api\ApiContext;
use App\Lib\RSA;
/**
 * 服务器端 RAS 接口协议
 *
 * @author Administrator
 */
class RSAServerProtocol {
	
	/**
	 * 错误提示
	 * @var string
	 */
	private $error = '';
	
	private $_remotePublicKey = '';
	private $_remotePublicKeyResource = '';
	private $_localPrivateKey = '';
	private $_localPrivateKeyResource = '';
	
	/**
	 * @var array
	 */
	private $params;
	
	public function __construct() {}
	
	/**
	 * 获取错误提示
	 * @return string
	 */	
	public function getError(  ){
		return $this->error;
	}
	/**
	 * 
	 * @param string $pubk		远端公钥字符串
	 * @return ApiProtocol
	 */
	public function setRemotePublicKey(string $pubk):RSAServerProtocol{
		$this->_remotePublicKey = $pubk;
		$this->_remotePublicKeyResource = RSA::getPublicKeyFromX509($pubk);
		return $this;
	}
	
	/**
	 * 
	 * @param string $prik		本地私钥字符串
	 * @return ApiProtocol
	 */
	public function setLocalPrivateKey(string $prik):RSAServerProtocol{
		$this->_localPrivateKey = $prik;
		$this->_localPrivateKeyResource = RSA::getPrivateKeyFromX509($prik);
		return $this;
	}
	
	
	public function setInput( string $input ):bool{
		$input = json_decode($input,true);
		if( !$input ){
			$this->error = '输入解析错误';
			return false;
		}
		$this->input = $input;
		return true;
	}
	
	public function getAppid():string{
		if( !$this->input ){
			throw new Exception('input 错误');
		}
		return $this->input['appid'];
	}
	public function getMethod():string{
		if( !$this->input ){
			throw new Exception('input 错误');
		}
		return $this->input['method'];
	}
	public function getParams():string{
		if( !$this->params ){
			throw new Exception('params 错误');
		}
		return $this->params;
	}
	
	
	/**
	 * 协议解析
	 * @param string $input
	 * @return bool
	 */
	public function unwrap( ):bool{
		$input = $this->input;
		$params = $input['params'];
		
		if( $input['sign_type'] == 'RSA' ){ // 根据算法，进行验签和解密处理
			$b = RSA::verify($input['params'], $input['sign'], $this->_remotePublicKeyResource);
			if( !$b ){
				$this->error = '数据验签失败';
				return false;
			}
			$params = RSA::decodeData($params, $this->_localPrivateKeyResource);
			if( !$params ){
				$this->error = '业务参数解密失败';
				return false;
			}
			$params =  json_decode($params,true);
			if( !$params ){
				$this->error = '业务参数格式错误';
				return false;
			}
			
			$this->params = $params;
		}
		
		return true;
	}
	
	/**
	 * 协议封装
	 * @param array $data
	 * @return string
	 * @throws Exception
	 */
	public function wrap( string $code='0', string $msg='', string $sub_code='', string $sub_msg='', array $data=[] ): string{
		$sing_type = '';
		$sign = '';
		if(count($data) ){ // 业务参数存在时，进行加密和签名处理
			$sing_type = 'RSA';
			$data = json_encode($data);
			$data = RSA::encodeData($data, $this->_remotePublicKeyResource);
			$sign = RSA::sign($data, $this->_localPrivateKeyResource);
		}else{
			$data = ['_'=>''];
		}
		$output = json_encode([
			'version' => '2.0',
			'sign_type' => $sing_type,
			'code' => $code,
			'msg' => $msg,
			'sub_code' => $sub_code,
			'sub_msg' => $sub_msg,
			'data' => $data,
			'sign' => $sign,
		]);;
		
		return $output;
	}
	
}
