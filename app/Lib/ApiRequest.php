<?php

namespace App\Lib;

/**
 * API 请求对象
 * 定义了接口请求交互协议
 * 用于:
 * 1）接收请求；
 * 2）发送请求；
 * @access public
 * @author Liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class ApiRequest {

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	private $appid = '';
	private $method = '';
	private $version = '1.0';
	private $userInfo = '';
	private $params = [];
	private $url = '';

	public function __construct() {
		
	}

	public function getAppid() {
		return $this->appid;
	}

	public function getMethod() {
		return $this->method;
	}

	public function setAppid(string $appid) {
		$this->appid = $appid;
		return $this;
	}

	public function setMethod(string $method) {
		$this->method = $method;
		return $this;
	}
	
	public function setVersion(string $version) {
		$this->version = $version;
		return $this;
	}


	/**
	 * 获取业务参数
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * 获取用户参数
	 * @return array
	 */
	public function getUserInfo() {
		return $this->userInfo;
	}

	/**
	 * 设置url
	 * @param array|string $params
	 * @throws \Exception
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * 设置参数
	 * @param array|string $params
	 * @throws \Exception
	 */
	public function setParams($params) {
		if (!is_string($params) && !is_array($params)) {
			throw new \Exception('method param error');
		}
		if (is_string($params)) {
			$params = json_decode($params, true);
		}
		$this->params = $params;
		return $this;
	}
	/**
	 * 设置用户信息
	 * @param array|string $userInfo
	 * @throws \Exception
	 */
	public function setUserInfo( $userInfo ) {
		if (!is_string($userInfo) && !is_array($userInfo)) {
			throw new \Exception('method param error');
		}
		if (is_string($userInfo)) {
			$userInfo = json_decode($userInfo, true);
		}
		$this->userInfo = $userInfo;
		return $this;
	}

	/**
	 * 发送请求
	 * @param string $method
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public function send($method = self::METHOD_POST) {
		if ($this->url == '') {
			throw new \Exception('ApiRequest illegal state');
		}
		$heders = [
			'Content-Type: application/json',
		];
		$jsonStr = '';
		if ($method == self::METHOD_POST) {
			$jsonStr = Curl::post($this->url, $this->toString(),$heders);
		} elseif ($method == self::METHOD_GET) {
			$jsonStr = Curl::get($this->url, $this->toString(),$heders);
		}
		//Common\LogApi::debug($this->url, $this->toString());
		$Response = new ApiResponse($jsonStr);
		return $Response;
	}

	/**
	 * 发送 POST请求
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public function sendPost() {
		$data = $this->send(self::METHOD_POST);
		return $data;
	}

	/**
	 * 发送 GET请求
	 * @return ApiResponse
	 * @throws \Exception
	 */
	public function sendGet() {
		$this->send(self::METHOD_GET);
	}

	/**
	 * 创建一个请求
	 * @param string|array $jsonStr 【必须】字符串是必须是标准的json；数组必须是关联数组
	 * 不管是json还是关联数组，必须包含：
	 * appid, method, timestamp, version, params, sign_type, sign
	 * @return ApiStatus
	 */
	public function create($jsonStr) {
		$status = new ApiStatus();
		$status->success(); // 默认为成功状态
		$this->status = $status;

		//-+--------------------------------------------------------------------
		// | 解析响应数据
		//-+--------------------------------------------------------------------
		if (strlen($jsonStr) == 0) {
			$status->setCode(ApiStatus::CODE_10100)->setMsg('空请求');
			return $status;
		}
		$data = json_decode($jsonStr, true);
		if (!is_array($data)) {
			if (empty($_POST)) {
				$status->setCode(ApiStatus::CODE_10101)->setMsg('非json格式');
				return $status;
			} else {
				$data = $_POST;
			}
		}
		//-+--------------------------------------------------------------------
		// | 校验参数
		//-+--------------------------------------------------------------------
		// appid 参数
		if (!isset($data['appid'])) {
			$status->setCode(ApiStatus::CODE_10102)->setMsg('code参数缺失');
			return $status;
		}
		// method 参数
		if (!isset($data['method'])) {
			$status->setCode(ApiStatus::CODE_10103)->setMsg('method参数缺失');
			return $status;
		}
		// version 参数
		if (!isset($data['version']) || !is_string($data['version'])) {
			$status->setCode(ApiStatus::CODE_10105)->setMsg('version错误');
			return $status;
		}

		// params 参数
		if (!isset($data['params'])) {
			if (is_string($data['params'])) {
				$data['params'] = json_decode($data['params'], true);
			}
			if (!is_array($data['params'])) {
				$status->setCode(ApiStatus::CODE_10106)->setMsg('params错误');
				return $status;
			}
		}
		// userinfo 参数
		if (isset($data['userinfo'])) {
			if (is_string($data['userinfo'])) {
				$data['userinfo'] = json_decode($data['userinfo'], true);
			}
		}

		//-+--------------------------------------------------------------------
		// | 赋值，初始化响应对象
		//-+--------------------------------------------------------------------
		$this->appid = $data['appid'];
		$this->method = $data['method'];
		$this->version = $data['version'];
		$this->params = $data['params'];
		$this->userInfo = $data['userinfo'];

		// 创建成功
		$this->status->setCode(ApiStatus::CODE_0);
		return $status;
	}

	/**
	 * 接收一个请求
	 * @return \app\common\Status
	 */
	public function receive() {
		$jsonStr = file_get_contents("php://input");
		return $this->create($jsonStr);
	}

	/**
	 * 将当前请求对象，转换成一个关联数组
	 * @return array	关联数组
	 */
	public function toArray() {
	    //添加验签
        $data = [
            'appid' => $this->appid,
            'method' => $this->method,
            'version' => $this->version,
            'params' => $this->params,
            'userinfo' => $this->userInfo,
            'sign_type'=>'MD5',
            'timestamp'=>date("Y-m-d H:i:s"),
        ];
        $sign = \app\Lib\Certificate\ApiUtil::generateSign($data);
        $data['sign'] = $sign;
		return $data;
	}

	public function toString() {
		return json_encode($this->toArray());
	}

	public function __toString() {
		$this->toString();
	}

}
