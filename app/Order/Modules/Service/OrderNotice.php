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
	 * 构造函数
	 * @param string	$order_no	订单编号
	 * @param string	$scene		场景
	 */
	public function __construct( string $order_no, string $scene ) {
		$this->order_no = $order_no;
		$this->scene = $scene;
	}
	
	
	/**
	 * 设置通知渠道
	 * @param int $channel
	 * @return $this
	 */
	public function setChannel( int $channel ){
		$this->channel = $channel;
		return $this;
	}

	public function asynNotify():bool{
		return \App\Lib\Common\JobQueueApi::addRealTime($this->scene.'-'.$this->order_no, env('APP_URL').'/order/notice/notify', [
			'order_no' => $this->order_no,
			'scene' => $this->scene,
		]);
	}
	
	/**
	 * 发送通知
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @throws \Exception	发生错误时抛出异常
	 */
	public function notify(){
		
		// 查询订单
		$order_info = [
			'order_no' => $this->order_no,
			'user_id' => '5',
			'realname' => '刘红星',
			'mobile' => '15311371612',
			'app_id' => '1',
		];
		
		if( $this->channel & self::SM ){
			$code = $this->getSMCode($this->scene, $order_info['app_id']);
			if( !$code ){
				\App\Lib\Common\LogApi::error('订单短息模板不存在', [
					'order_no' => $this->order_no,
					'scene' => $this->scene,
					'app_id' => $order_info['app_id'],
				]);
			}
			//\App\Lib\Common\SmsApi::sendMessage($order_info['mobile'], $code, $order_info);
		} 
		
		return true;
	}
	
	
	private function getSMCode( $scene, $app_id ){
		
		$arr = [
			'1' => [
				'order_created1' => '123456',
				'order_cancel' => '123456',
			],
			'91' => [
				
			],
		];
		if( !isset($arr[$app_id]) ){
			$app_id = 1;
		}
		if( !isset($arr[$app_id][$scene]) ){
			return false;
		}
		return $arr[$app_id][$scene];		
	}
	
}
