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
	 * 微信支付
	 */
	const Wechat = 4;
    /**
     * 乐百分支付
     */
    const Lebaifen = 5;
	/**
	 * 线下支付
	 */
	const UnderLine = 6;

	/**
	 * 订单入账方式
	 * @return array
	 */
	public static function getBusinessType(){
		return [
			self::Unionpay 	=> '银联',
			self::Alipay 	=> '支付宝',
			self::Jdpay 	=> '京东支付',
			self::Wechat 	=> '微信支付',
			self::Lebaifen 	=> '乐百分支付',
			self::UnderLine => '线下支付',
		];
	}
    /**
     *
     * 订单入账方式 转换成 订单入账方式名称
     * @param int $status   订单入账方式
     * @return string 订单入账方式名称
     */
    public static function getBusinessName($status){
        $list = self::getBusinessType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
}
