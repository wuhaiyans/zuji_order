<?php
namespace App\Order\Modules\Repository\Pay;

/**
 */
class PaymentStatus {
	
	const NO_PAYMENT	= 0;	// 无效
	const WAIT_PAYMENT	= 1;	// 待支付
	const PAYMENT_SUCCESS		= 2;	// 成功
}
