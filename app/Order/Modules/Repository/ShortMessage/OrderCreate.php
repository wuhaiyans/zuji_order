<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * OrderCreated
 *
 * @author Administrator
 */
class OrderCreate implements ShortMessage {
	
	private $order_info;
	
	/**
	 * 
	 * @param array $order_info
	 */
	public function __construct( array $order_info ) {
		$this->order_info = $order_info;
	}


	public function getCode(){
		return Config::getCode($this->order_info['appid'], 'order_create');
	}
	
	public function notify(){
		// 短息模板
		$code = $this->getCode();
		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($this->order_info['mobile'], $code, [
			'order_no' => $this->order_info['order_no'],
		]);
	}
	
}
