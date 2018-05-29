<?php

namespace App\Lib;

/**
 * API 响应对象
 * 定义了接口响应交互协议
 * 用于:
 * 1）接收响应；
 * 2）发送响应；
 * @access public
 * @author Liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class ApiResponse {

	/**
	 * 状态对象
	 * @var ApiStatus
	 */
	private $status = null;
	private $code = ApiStatus::CODE_50000;
	private $msg = '';
	private $data = '';
	
	private $originalValue = '';

	/**
	 * 构造函数，初始化当前响应对象
	 * @param string json格式字符串
	 * @return type
	 */

	public function __construct($jsonStr = '') {
		$this->originalValue = $jsonStr;
		$status = new ApiStatus();
		$status->success(); // 默认为成功状态
		$this->status = $status;
		if ($jsonStr == '') {
			return;
		}
		Common\LogApi::debug('ApiResponse初始化', $jsonStr);
		//-+--------------------------------------------------------------------
		// | 解析响应数据
		//-+--------------------------------------------------------------------
		if (strlen($jsonStr) == 0) {
			$status->setCode(ApiStatus::CODE_10100)->setMsg('空响应');
			return;
		}
		$data = json_decode($jsonStr, true);
		if (!is_array($data)) {
			$status->setCode(ApiStatus::CODE_10101)->setMsg('非json格式');
			return;
		}

		//-+--------------------------------------------------------------------
		// | 校验响应参数
		//-+--------------------------------------------------------------------
		// code 参数
		if (!isset($data['code'])) {
			$status->setCode(ApiStatus::CODE_10102)->setMsg('code参数缺失');
			return;
		}
		$this->code = $data['code'];
		if (!isset($data['msg'])) {
			$status->setCode(ApiStatus::CODE_10102)->setMsg('msg参数缺失');
			return;
		}
		$this->msg = $data['msg'];
		// data 参数
		if (!is_array($data['data'])) {
			$status->setCode(ApiStatus::CODE_10104)->setMsg('data参数缺失或不是字符串');
			return;
		}

		if ($data['code'] != 0) {
			$status->setCode( $data['code'] )->setMsg( $data['msg'] );
			return;
		}

		//-+--------------------------------------------------------------------
		// | 赋值，初始化响应对象
		//-+--------------------------------------------------------------------

		$this->data = $data['data'];

		// 创建成功
		$this->status->setCode(ApiStatus::CODE_0);
	}

	/**
	 * 设置错误码
	 * @param string $code  错误码
	 */
	public function setCode($code) {
		$this->code = $code;
		return $this;
	}

	public function setMsg($msg) {
		$this->msg = $msg;
		return $this;
	}

	/**
	 * 设置响应业务参数
	 * @param mixed $data   【必须】返回业务参数，字符串必须是json格式；数组必须是关联数组
	 */
	public function setData($data) {
		if (!$data) {
			$this->data = ['_' => ''];
			return $this;
		}
		if (is_string($data)) {
			$data = json_decode($data, true);
		}
		if (!is_array($data)) {
			exit('ApiResponse::setData() error');
		}
		$this->data = $data;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}
	
	public function getOriginalValue() {
		return $this->originalValue;
	}

	/**
	 * 判断接口响应是否正确
	 * @return boolean
	 */
	public function isSuccessed() {
		return $this->status->isSuccessed();
	}

	/**
	 * 
	 * @return ApiStatus
	 */
	public function getStatus() {
		return $this->status;
	}

	public function flush() {
		echo $this->toString();
	}

	public function toArray() {
		return array(
			'code' => $this->code,
			'msg' => $this->msg,
			'data' => $this->data,
		);
	}

	public function toString() {
		return json_encode($this->toArray());
	}

	public function __toString() {
		$this->toString();
	}

}
