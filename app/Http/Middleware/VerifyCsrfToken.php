<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
		'/order/pay/paymentNotify',				// 支付通知
		'/order/pay/refundNotify',				// 退款通知
		'/order/pay/withholdSignNotify',		// 代扣签约通知
		'/order/pay/withholdUnsignNotify',		// 代扣解约通知
		'/order/pay/withholdCreatePayNotify',	// 代扣扣款异步通知
		'/order/pay/fundauthNotify',			// 资金预授权冻结通知
		'/order/pay/fundauthUnfreezeNotify',	// 资金预授权解冻通知
		'/order/pay/fundauthToPayNotify',		// 资金预授权转支付通知
		'/order/pay/deduDepositNotify',		// 扣除押金通知

		'/order/mini/withholdingCloseCancelNotify',	// 小程序订单回调接口POST请求通知

		//
		'/order/notice/notify',	// 订单通知
		
		//
		'/common/job/testJobCustomer',	// 任务消费者测试
		//
		'/common/test/testServer',	// 测试接口
		
		'/common/pay/testAlipayRefund',
    ];
}
