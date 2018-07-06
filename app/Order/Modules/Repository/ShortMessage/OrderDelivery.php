<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Lib\User\User;
use App\Order\Modules\Repository\OrderDeliveryRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\Channel;

/**
 * OrderDelivery 订单发货
 *
 * @author wuhaiyan
 */
class OrderDelivery implements ShortMessage {
	
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
		$orderDelivery = OrderDeliveryRepository::getOrderDelivery($this->business_no);

		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName'=>$orderInfo['realname'],
            'orderNo'=>$orderInfo['order_no'],
            'logisticsNo'=>$orderDelivery['logistics_no'],
		]);
	}

	// 支付宝 短信通知
	public function alipay_notify(){
        //通过用户id查询支付宝用户id
        // 查询订单
        $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$this->business_no));
        if( !$orderInfo ){
            return false;
        }
        $goods = OrderRepository::getGoodsListByOrderId($this->business_no);
        if(!$goods){
            return false;
        }
        $goodsName ="";
        foreach ($goods as $k=>$v){
            $goodsName.=$v['goods_name']." ";
        }
        //获取物流单号
        $delivery_info = OrderDeliveryRepository::getOrderDelivery($this->business_no);
        if(!$delivery_info){
            return false;
        }

        $userAlipay = User::getUserAlipayId($orderInfo['user_id']);
        if(!is_array($userAlipay)){
            return false;
        }
        if(!empty($userAlipay['alipay_user_id'])) {
            //通过用户id查询支付宝用户id
            $MessageSingleSendWord = new \App\Lib\AlipaySdk\sdk\MessageSingleSendWord($userAlipay['alipay_user_id']);
            $message_arr = [
                'goods_name' => $goodsName,
                'amount' => $orderInfo['order_amount']+$orderInfo['order_yajin']+$orderInfo['order_insurance'],
                'order_no' => $orderInfo['order_no'],
                'fast_mail_no' => $delivery_info['logistics_no'],
            ];
            $b = $MessageSingleSendWord->SendGoods($message_arr);
            if ($b === false) {
                LogApi::error("支付宝消息推送失败",$message_arr);
            }
        }
        return true;

	}
//	public function notify($data=[]){
//		$result = \App\Lib\Common\SmsApi::sendMessage('18201062343', $this->getCode(1), ['goodsName'=>'iphone x']);
//		var_dump($result);exit;
//	}

	
}
