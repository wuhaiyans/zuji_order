<?php
namespace App\Lib\Payment;

/**
 * 统一退款接口
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class CommonRefundApi extends \App\Lib\BaseApi {
    /**
     * 退款申请接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param array $params		业务请求参数
     * [
     *		'name'			=> '', //交易名称
     *		'out_refund_no' => '', //业务系统退款码
     *		'payment_no'	=> '', //支付系统支付码
     *		'amount'		=> '', //支付金额；单位：分
     *		'refund_back_url' => '', //退款回调URL
     * ]
     * @return array		业务返回参数
     * [
     * 		'out_refund_no'	=> '', //订单系统退款码
     * 		'refund_no'		=> '', //支付系统退款码
	 *		'status'		=> '',// 状态 success：支付成功；processing：处理中；其他值为未完成支付
     * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function apply( array $params ){
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'),'pay.refund.apply', '1.0', $params);
    }
	
    /**
     * 退款查询接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param array $params		业务请求参数
     * [
     *		'refund_no'		=> '', //支付系统退款码
     *		'out_refund_no' => '', //业务系统退款码
     * ]
     * @return array		业务返回参数
     * [
     *		'refund_no'		=> '', //支付系统退款码
     *		'out_refund_no' => '', //业务系统退款码
     *		'status'		=> '', //状态：success：交易成功；init：已初始化；processing：退款处理中
     *		'trade_time'		=> '', //交易时间戳
     * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function query( array $params ){
		return self::request(\config('paysystem.PAY_APPID'), config('paysystem.PAY_API'),'pay.refund.query', '1.0', $params);
    }
}