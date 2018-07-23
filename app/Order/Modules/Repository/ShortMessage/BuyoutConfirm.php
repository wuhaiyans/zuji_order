<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Service\OrderBuyout;

/**
 * OrderCancel
 *
 * @author limin
 */
class BuyoutConfirm implements ShortMessage {
	
	private $business_type;
	private $business_no;
	private $data;

	public function setBusinessType( int $business_type ){
		$this->business_type = $business_type;
	}
	
	public function setBusinessNo( string $business_no ){
		$this->business_no = $business_no;
	}

	public function setData( array $data ){
		$this->data = $data;
	}

	public function getCode($channel_id){
	    $class =basename(str_replace('\\', '/', __CLASS__));
		return Config::getCode($channel_id, $class);
	}

	
	public function notify(){
		// 根据业务，获取短息需要的数据
		$buyoutInfo = OrderBuyout::getInfo($this->business_no);
		if( !$buyoutInfo ){
			return false;
		}
		// 查询订单
        $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$buyoutInfo['order_no']));
		if( !$orderInfo ){
			return false;
		}
		// 短息模板
		$code = $this->getCode($orderInfo['channel_id']);
		if( !$code ){
			return false;
		}

		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName'=>$orderInfo['realname'],
            'buyoutPrice'=>$buyoutInfo['amount'],
		]);
	}

	// 支付宝 短信通知
	public function alipay_notify(){
		return true;
	}

	
}
