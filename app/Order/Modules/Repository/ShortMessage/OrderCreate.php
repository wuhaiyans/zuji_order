<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\Channel;

/**
 * OrderCreate
 *
 * @author wuhaiyan
 */
class OrderCreate implements ShortMessage {
	
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
        $class =basename(str_replace('\\', '/', __CLASS__));
        \App\Lib\Common\LogApi::info('message-orderCreate',[
            'channel_id'=>$orderInfo['channel_id'],
            'code'=>$code,
            'class'=>$class,
            ]);
        $goods = OrderRepository::getGoodsListByOrderId($this->business_no);
		if(!$goods){
		    return false;
        }
        $goodsName ="";
        foreach ($goods as $k=>$v){
            $goodsName.=$v['goods_name']." ";
        }



		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'goodsName'=>$goodsName,
            'realName'=>$orderInfo['realname'],
            'orderNo'=>$orderInfo['order_no'],
            'lianjie'=>createShortUrl('https://h5.nqyong.com/index?appid='.$orderInfo['appid']),
		]);
	}

	// 支付宝 短信通知
	public function alipay_notify(){
		return true;
	}
//	public function notify($data=[]){
//		$result = \App\Lib\Common\SmsApi::sendMessage('18201062343', $this->getCode(1), ['goodsName'=>'iphone x']);
//		var_dump($result);exit;
//	}

	
}
