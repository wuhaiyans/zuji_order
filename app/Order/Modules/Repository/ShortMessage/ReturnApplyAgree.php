<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\Channel;

/**
 * OrderCreated
 *
 * @author Administrator
 */
class ReturnApplyAgree implements ShortMessage {
	
	private $business_type;
	private $business_no;
	
	public function setBusinessType( int $business_type ){
		$this->business_type = $business_type;
	}
	
	public function setBusinessNo( string $business_no ){
		$this->business_no = $business_no;
	}

	public function getCode($channel_id){
	    $class =basename(str_replace('\\', '/', __CLASS__));
		return Config::getCode($channel_id, $class);
	}
	
	public function notify($data=[]){
		// 根据业务，获取短息需要的数据
		
		// 查询订单
        $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$this->business_no));
		if( !$orderInfo ){
			return false;
		}
		// 短息模板
		$code = $this->getCode($orderInfo['channel_id']);
		if( !$code ){
			return false;
		}
        $goods = OrderRepository::getGoodsListByOrderId($this->business_no);
		if(!$goods){
		    return false;
        }

		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName' => $data['realName'],
            'orderNo' => $data['orderNo'],
            'goodsName' => $data['goodsName'],
            'shoujianrenName' => $data['shoujianrenName'],
            'returnAddress' => $data['returnAddress'],
            'serviceTel'=>Config::Customer_Service_Phone,
        ],$data['orderNo']);
	}
	
}
