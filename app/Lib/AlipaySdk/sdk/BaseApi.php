<?php
namespace App\Lib\AlipaySdk\sdk;

use App\Lib\AlipaySdk\sdk\aop\AopClient;

/**
 * Description of BaseApi
 *
 * @author Administrator
 */
include_once __DIR__ . '/AopSdk.php';
include_once __DIR__ . '/function.inc.php';

class BaseApi {

	protected $error = '[支付宝]接口请求成功';


	protected $appid = 'default';
	protected $config = [];

	/**
	 *
	 * @var \App\Lib\AlipaySdk\sdk\aop\AopClient
	 */
	protected $Aop = NULL;

	public function __construct($appid) {
		echo 11;die;

		$config_file = __DIR__ . '/' . $appid . '-config.php';
		if (!file_exists($config_file) && !is_readable($config_file)) {
			throw new \Exception('支付宝应用配置未找到:' . $config_file);
		}
		$config = include $config_file;
		$aop = new AopClient ();
		$aop->gatewayUrl = $config ['gatewayUrl'];
		$aop->appId = $config ['app_id'];
		$aop->rsaPrivateKey = $config['merchant_private_key'];
		$aop->alipayrsaPublicKey = $config['alipay_public_key'];
		$aop->signType = $config['sign_type'];
		$aop->apiVersion = "1.0";
		// 开启页面信息输出
		$aop->debugInfo = $config['debug_info'];

		$this->Aop = $aop;
		$this->config = $config;
	}

	public function pageExecute($request, $ispage = false, $type = "POST") {
		if ($ispage) {
			return $this->Aop->pageExecute($request, $type);
		}
		return $this->Aop->execute($request);
	}

	/**
	 * 访问接口
	 * @param type $request
	 * @param type $token
	 * @return mixed  false:发生错误；array:成功
	 */
	public function execute($request, $token = '') {
		try {
			$result = $this->Aop->execute($request, $token);
			$result = json_decode(json_encode($result),true);
			$method = $request->getApiMethodName();
			$method = str_replace('.', '_', $method).'_response';
			if( $result && $result[$method]){
				$this->error = '';
				return $result[$method];
			}
		} catch (\Exception $exc) {
			$this->error = $exc->getMessage();
			return false;
		}
		$this->error = '接口协议错误';
		return false;
	}

	/**
	 * 签名校验
	 * @param type $params
	 * @param type $sign
	 * @return boolean
	 */
	public function verify($params) {
		$this->Aop->alipayrsaPublicKey = $this->config['alipay_public_key'];
		$result = $this->Aop->rsaCheckV1($params, $this->config['alipay_public_key'], $this->config['sign_type']);
		return $result;
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getError(){
		return $this->error;
	}

}
