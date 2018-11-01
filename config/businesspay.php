<?php

/**
 * 
 */
return [

	'business' => [
	    // 业务类型为【买断】的工厂实例
	    \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT => App\Order\Modules\Repository\Buyout\Buyout::class,
	],
];
