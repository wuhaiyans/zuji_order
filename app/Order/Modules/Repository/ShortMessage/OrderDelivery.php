<?php

namespace App\Order\Modules\Repository\ShortMessage;

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
        $this->certification_alipay = $this->load->service('member2/certification_alipay');
        $to_user_id = $this->certification_alipay->get_last_info_by_user_id($order_info['user_id']);
        if(!empty($to_user_id['user_id'])) {
            $MessageSingleSendWord = new \alipay\MessageSingleSendWord($to_user_id['user_id']);
            $message_arr = [
                'goods_name' => $order_info['goods_name'],
                'amount' => $order_info['amount'],
                'order_no' => $order_info['order_no'],
                'fast_mail_no' => $_POST['logistics_sn'],
            ];
            $b = $MessageSingleSendWord->SendGoods($message_arr);
            if ($b === false) {
                \zuji\debug\Debug::error(\zuji\debug\Location::L_Trade, 'SendGoods', $MessageSingleSendWord->getError());
            }
        }
	}
//	public function notify($data=[]){
//		$result = \App\Lib\Common\SmsApi::sendMessage('18201062343', $this->getCode(1), ['goodsName'=>'iphone x']);
//		var_dump($result);exit;
//	}

	
}
