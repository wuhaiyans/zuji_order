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
	 * @param array $params  二选一参数,优先使用payment_no
	 * [
	 *		'payment_no'		=> '',// 支付系统 支付交易码
	 *		'out_payment_no'	=> '',// 业务系统 支付交易码
	 * ]
	 * @return bool：确认收货成功
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
	 */
	public static function confirmReceipt( array $params ){
		self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'),'pay.lebaifen.payment.confirmReceipt', '1.0', $params);
		return true;
	}

	/**
	 * 获取乐百分支付信息
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array $params  二选一参数,优先使用payment_no
	 * [
	 *		'payment_no'		=> '',// 支付系统 支付交易码
	 *		'out_payment_no'	=> '',// 业务系统 支付交易码
	 * ]
	 * @return array	百分支付信息详情
	 * [
	 *		'payment_no'	=> '',	// 支付系统 支付交易码
	 *		'out_payment_no'=> '',	// 业务系统 支付交易码
	 *		'status'		=> '',	// 状态；0：未支付；1：已支付；2：已结束
	 *		'txn_amount'	=> '',	// 总金额；单位：分
	 *		'txn_terms'		=> '',	// 总分期数
	 *		'rent_amount'	=> '',	// 总租金；单位：分
	 *		'month_amount'	=> '',	// 每月租金；单位：分
	 *		'remainder_amount' => '',	// 每月租金取整后,总租金余数；单位：分
	 *		'first_other_amount' => '',// 首期额外金额；单位：分
	 *		'sum_amount'	=> '',	// 已还总金额；单位：分
	 *		'sum_terms'		=> '',	// 已还总期数；单位：分
	 *		'remain_amount' => '',	// 剩余总金额；单位：分
	 * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
	 */
	public static function getPaymentInfo( array $params ){
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'),'pay.lebaifen.payment.info', '1.0', $params);
	}
	
	
}
