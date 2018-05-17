<?php
/**
 * 订单支付接口文件 （作废，支付不以该形式封装）
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */
namespace App\Lib\Order;
use App\Lib\Curl;

/**
 * Payment 订单支付接口
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Payment extends OrderApi{
	
	
	
	/**
	 * 支付成功异步通知
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param	array	$params		业务请求参数
	 * @return	array				业务返回参数
	 * [
	 *		'payment_no'	=> '',	//[必须]stirng	支付系统支付码
	 *		'out_no'		=> '',	//[必须]stirng	业务系统支付码
	 *		'status'		=> '',	//[必须]int		状态：0：成功；1：失败
	 *		'reason'		=> '',	//[必须]stirng	失败原因
	 * ]
	 * @throws \Exception			请求失败时抛出异常
	 */
	public function paymentNotify( array $params ){
		$method = 'order.pay.payment.notify';
		return self::request($method, $params);
	}
	
	/**
	 * 代扣签约异步通知
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param	array	$params		业务请求参数
	 * @return	array				业务返回参数
	 * [
	 *		'agreement_no'	=> '',	//[必须]stirng	支付系统代扣签约码
	 *		'out_agreement_no'=> '',//[必须]stirng	业务系统代扣签约码
	 *		'status'		=> '',	//[必须]int		状态：0：成功；1：失败
	 *		'reason'		=> '',	//[必须]stirng	失败原因
	 * ]
	 * @throws \Exception			请求失败时抛出异常
	 */
	public function withholdSignNotify( array $params ){
		$method = 'order.pay.withhold.sign.notify';
		return self::request($method, $params);
	}
	
	/**
	 * 代扣解约异步通知
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param	array	$params		业务请求参数
	 * @return	array				业务返回参数
	 * [
	 *		'agreement_no'	=> '',	//[必须]stirng	支付系统代扣签约码
	 *		'out_agreement_no'=> '',//[必须]stirng	业务系统代扣签约码
	 *		'status'		=> '',	//[必须]int		状态：0：成功；1：失败
	 *		'reason'		=> '',	//[必须]stirng	失败原因
	 * ]
	 * @throws \Exception			请求失败时抛出异常
	 */
	public function withholdUnsignNotify( array $params ){
		$method = 'order.pay.withhold.unsign.notify';
		return self::request($method, $params);
	}
}
