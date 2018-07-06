<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\Channel;

/**
 * OrderDayReceive 长租确认收货
 *
 * @author wuhaiyan
 */
class OrderMonthReceive implements ShortMessage {
	
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

        $instalment = OrderGoodsInstalmentRepository::getInfo(['order_no'=>$this->business_no,'times'=>1]);
        if(!$instalment){
            return false;
        }

         $createTime =$instalment['term'].$instalment['day'];


		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName'=>$orderInfo['realname'],
            'orderNo'=>$orderInfo['order_no'],
            'goodsName'=>$goodsName,
            'beginTime'=>date("Y-m-d H:i:s",$beginTime),
            'zuQi'=>$zuqi,
            'endTime'=>date("Y-m-d H:i:s",$endTime),
            'zuJin'=>$zujin,
            'createTime'=>$createTime, //扣款日期
		]);
	}

	// 支付宝 短信通知
	public function alipay_notify(){
        //通过用户id查询支付宝用户id
        $this->certification_alipay = $this->load->service('member2/certification_alipay');
        $to_user_id = $this->certification_alipay->get_last_info_by_user_id($order_info['user_id']);
        if(!empty($to_user_id['user_id'])) {
            //获取物流信息
            $MessageSingleSendWord = new \alipay\MessageSingleSendWord($to_user_id['user_id']);
            $message_arr = [
                'goods_name' => $order_info['goods_name'],
                'fast_mail_company' => '顺丰速运',
                'fast_mail_no' => $delivery_info['wuliu_no'],
                'sing_time' => date('Y-m-d H:i:s'),
                'order_no' => $order_info['order_no'],
            ];
            $b = $MessageSingleSendWord->SignIn($message_arr);
            if ($b === false) {
                Debug::error(Location::L_Order, 'SignIn', $MessageSingleSendWord->getError());
            }
        }
		return true;
	}
//	public function notify($data=[]){
//		$result = \App\Lib\Common\SmsApi::sendMessage('18201062343', $this->getCode(1), ['goodsName'=>'iphone x']);
//		var_dump($result);exit;
//	}

	
}
