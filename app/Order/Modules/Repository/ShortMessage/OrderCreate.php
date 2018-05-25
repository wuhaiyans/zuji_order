<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderRepository;

/**
 * OrderCreated
 *
 * @author Administrator
 */
class OrderCreate implements ShortMessage {
	
	private $business_type;
	private $business_no;
	
	public function setBusinessType( int $business_type ){
		$this->business_type = $business_type;
	}
	
	public function setBusinessNo( string $business_no ){
		$this->business_no = $business_no;
	}

	public function getCode(){
		return Config::getCode($this->order_info['appid'], __CLASS__);
	}
	
	public function notify(){
		// 根据业务，获取短息需要的数据
		
		// 查询订单
		$order_info = OrderRepository::getOrderInfo(array('order_no'=>$this->business_no));
		
		if( !$order_info ){
			return false;
		}
		
		// 短息模板
		$code = Config::getCode($channelId, __CLASS__);
		if( !$code ){
			return false;
		}
		
		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($order_info['mobile'], $code, [
			'order_no' => '',
		]);
	}
	
}
