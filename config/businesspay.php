<?php

/**
 * 业务配置
 * int 业务标识 => 业务class
 */
return [

	'business' => [
	    // 业务类型为【买断】的工厂实例
	    \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT => App\Order\Modules\Repository\Buyout\Buyout::class,
		// 业务类型为【分期主动支付】的工厂实例
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI => App\Order\Modules\Repository\Instalment\Instalment::class,
	    // 业务类型为【还机】的工厂实例
	    \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK => \App\Order\Modules\Repository\Giveback\GivebackPay::class,
	],
];
