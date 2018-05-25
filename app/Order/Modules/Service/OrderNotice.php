<?php

/**
 * App\Order\Modules\Service\OrderNotice.php
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace App\Order\Modules\Service;


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
	private $business_type;
	/**
	 *
	 * @var string
	 */
	private $business_no;
	/**
	 *
	 * @var string
	 */
	private $scene;
	
	
	/**
	 * 构造函数
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param string	$order_no	订单编号
	 * @param string	$scene		场景
	 */
	public function __construct( string $business_type, string $business_no, string $scene ) {
		$this->business_type = $business_type;
		$this->business_no = $business_no;
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
			'business_type' => $this->business_type,
			'business_no' => $this->business_no,
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
				
		if( $this->channel & self::SM ){
			// 短信
			$short_message = $this->getShortMessage( );
			if( !$short_message ){
				\App\Lib\Common\LogApi::error('订单短息模板不存在', [
					'business_type' => $this->business_type,
					'business_no' => $this->business_no,
					'scene' => $this->scene,
				]);
			}
			
			// 通知
			$b = $short_message->notify();
			\App\Lib\Common\LogApi::debug('短信通知',[
					'business_type' => $this->business_type,
					'business_no' => $this->business_no,
					'scene' => $this->scene,
					'status' => $b?'success':'failed',
			]);
		} 
		
		return true;
	}
	
	/**
	 * 根据场景获取短息
	 * @return \App\Order\Modules\Repository\ShortMessage\ShortMessage
	 */
	private function getShortMessage( ){
//		$arr = [
//			'order_create' => '\App\Order\Modules\Repository\ShortMessage\OrderCreate',
//			'order_cancel' => '\App\Order\Modules\Repository\ShortMessage\OrderCancel',
//		];
//		if( !isset($arr[$this->scene]) ){
//			return false;
//		}
//		$short_message = new $arr[$this->scene]( );
		
		$className = '\App\Order\Modules\Repository\ShortMessage\\' . $this->scene;
		if(!class_exists($className) ){
			return false;
		}
		$short_message = new $className;
		$short_message->setBusinessType( $this->business_type );
		$short_message->setBusinessNo( $this->business_no );
		return $short_message;
	}
	
}
