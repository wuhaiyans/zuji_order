<?php

/**
 * App\Order\Modules\Service\OrderNotice.php
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderRepository;

/**
 * 订单通知
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderNotice{
	
	const SM			= 1;		// 短信
	const Email			= 1<<1;		// 邮件
	const Message		= 1<<2;		// 消息
	
	// 所有
	const All = self::SM
			| self::Email
			| self::Message;
	
	
	/**
	 * 通知渠道
	 * @var int
	 */
	private $channel = self::All;
	
	/**
	 *
	 * @var string
	 */
	private $order_no;
	/**
	 *
	 * @var string
	 */
	private $scene;
	
	/**
	 * 订单基本信息
	 * @var array
	 */
	private $order_info;
	
	/**
	 * 构造函数
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param string	$order_no	订单编号
	 * @param string	$scene		场景
	 */
	public function __construct( string $order_no, string $scene ) {
		$this->order_no = $order_no;
		$this->scene = $scene;
	}
	
	
	/**
	 * 设置通知渠道
	 * 一个二进制位代表一个渠道，1:表示需要；0：不需要
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param int $channel		渠道选项
	 * @return $this
	 */
	public function setChannel( int $channel ){
		$this->channel = $channel;
		return $this;
	}

	/**
	 * 异步通知
	 * 通过任务系统实现异步处理
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function asyncNotify():bool{
		return \App\Lib\Common\JobQueueApi::addRealTime($this->scene.'-'.$this->order_no, env('APP_URL').'/order/notice/notify', [
			'order_no' => $this->order_no,
			'scene' => $this->scene,
		]);
	}
	
	/**
	 * 同步通知
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @throws \Exception	发生错误时抛出异常
	 */
	public function notify(){
		
		// 查询订单
		$this->order_info = OrderRepository::getOrderInfo(array('order_no'=>$this->order_no));
		if( !$this->order_info ){
			return false;
		}
		
		if( $this->channel & self::SM && $this->order_info['mobile'] ){
			// 短信
			$short_message = $this->getShortMessage( );
			if( !$short_message ){
				\App\Lib\Common\LogApi::error('订单短息模板不存在', [
					'order_no' => $this->order_no,
					'scene' => $this->scene,
					'appid' => $this->order_info['appid'],
				]);
			}
			\App\Lib\Common\LogApi::debug('短信通知',[
					'order_no' => $this->order_no,
					'scene' => $this->scene,
					'mobile' => $this->order_info['mobile'],
					'templateCode' => $short_message->getCode(),
			]);
			
			// 通知
			$short_message->notify();
			//\App\Lib\Common\SmsApi::sendCode( $order_info['mobile'] );
		} 
		
		return true;
	}
	
	/**
	 * 根据场景获取短息
	 * @return \App\Order\Modules\Repository\ShortMessage\ShortMessage
	 */
	private function getShortMessage( ){
		
		$arr = [
			'order_create' => '\App\Order\Modules\Repository\ShortMessage\OrderCreate',
			'order_cancel' => '\App\Order\Modules\Repository\ShortMessage\OrderCancel',
		];
		if( !isset($arr[$this->scene]) ){
			return false;
		}
		return new $arr[$this->scene]( $this->order_info );
	}
	
}
