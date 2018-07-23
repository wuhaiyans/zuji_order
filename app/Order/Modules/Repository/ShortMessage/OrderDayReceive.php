<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Lib\User\User;
use App\Order\Modules\Repository\OrderDeliveryRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\Channel;

/**
 * OrderDayReceive 短租确认收货
 *
 * @author wuhaiyan
 */
class OrderDayReceive implements ShortMessage {
	
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
        $goods = OrderRepository::getGoodsListByOrderId($this->business_no);
		if(!$goods){
		    return false;
        }
        $goodsName ="";
        foreach ($goods as $k=>$v){
            $goodsName.=$v['goods_name']." ";
            $beginTime =$v['begin_time'];
            $endTime =$v['end_time'];
            $zuqi =$v['zuqi'];
            $zujin =$v['zujin'];
        }

		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName'=>$orderInfo['realname'],
            'orderNo'=>$orderInfo['order_no'],
            'goodsName'=>$goodsName,
            'beginTime'=>date("Y-m-d H:i:s",$beginTime),
            'zuQi'=>$zuqi,
            'endTime'=>date("Y-m-d H:i:s",$endTime),
            'zuJin'=>$zujin,
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
                'fast_mail_company' => '顺丰速运',
                'fast_mail_no' => $delivery_info['logistics_no'],
                'sing_time' => date('Y-m-d H:i:s'),
                'order_no' => $orderInfo['order_no'],
            ];
            $b = $MessageSingleSendWord->SignIn($message_arr);
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
