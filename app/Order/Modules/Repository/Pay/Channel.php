<?php
namespace App\Order\Modules\Repository\Pay;

/**
 * 支付渠道
 *
 * @author 
 */
class Channel {
	
	/**
	 * 银联
	 */
	const Unionpay = 1;
	
	/**
	 * 支付宝
	 */
	const Alipay = 2;
	/**
	 * 京东支付
	 */
	const Jdpay = 3;


	/**
	 * 订单入账方式
	 * @return array
	 */
	public static function getBusinessType(){
		return [
			self::Unionpay 	=> '银联',
			self::Alipay 	=> '支付宝',
			self::Jdpay 	=> '京东支付',
		];
	}


}
