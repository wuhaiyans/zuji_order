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
	 * 线下支付 1银行转账
	 */
	const UnderBank = 1;
	/**
	 * 线下支付 2支付宝转账
	 */
	const UnderAlipay = 2;
	/**
	 * 线下支付 3微信转账
	 */
	const UnderWeChat = 3;

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
			self::UnderLine 	=> '线下支付',
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

	/**
	 * 线下支付方式
	 * @return array
	 */
	public static function getUnderLineBusinessType(){
		return [
			self::UnderBank 	=> '银行转账',
			self::UnderAlipay 	=> '支付宝转账',
			self::UnderWeChat 	=> '微信转账',
		];
	}

	/**
	 * 获取线下支付方式方式名称
	 * @param  $type int 线下入账方式类型
	 * @return string 线下入账方式名称
	 */
	public static function getUnderLineBusinessTypeName(int $type):string {
		$list = self::getUnderLineBusinessType();
		return $list[$type];
	}



}
