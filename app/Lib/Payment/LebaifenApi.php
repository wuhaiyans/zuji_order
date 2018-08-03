<?php
namespace App\Lib\Payment;

/**
 * 乐百分接口
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class LebaifenApi extends \App\Lib\BaseApi {
	
	/**
	 * 确认收货接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array $params  二选一参数，优先使用payment_no
	 * [
	 *		'payment_no'		=> '',// 支付系统 支付交易码
	 *		'out_payment_no'	=> '',// 业务系统 支付交易码
	 * ]
	 * @return bool	true：确认收货成功
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
	 */
	public static function confirmReceipt( array $params ){
		self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'),'pay.lebaifen.payment.confirmReceipt', '1.0', $params);
		return true;
	}

}
