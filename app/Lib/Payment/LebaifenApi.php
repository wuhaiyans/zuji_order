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
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
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
	 *		'sum_terms'		=> '',	// 已还总期数
	 *		'remain_amount' => '',	// 剩余总金额；单位：分
	 * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
	 */
	public static function getPaymentInfo( array $params ){
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'),'pay.lebaifen.payment.info', '1.0', $params);
	}

    /**
     *
     * 乐百分买断或者还机完成调用的接口
     * Author: heaven
     * @param array $params  二选一参数,优先使用payment_no
     * [
     *		 "payment_no":"10A92662696246007", //支付系统的支付单号
     *      "amount":"123",                    //要扣的押金金额；单位：分
     *      "back_url":"http://_._.com"        //异步通知的url地址
     * ]
     * @param array $params
     * @return array
     */
    public static function backRefund( array $params ){
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
        return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'),'pay.lebaifen.payment.backRefund', '1.0', $params);
    }
	
}
