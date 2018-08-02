<?php

$order_domain = env('ORDER_DOMAIN', 'http://order.nqyong.com:1081');
$old_order_domain = env('OLD_ORDER_DOMAIN', 'http://biz.nqyong.com:1081');

// 订单系统系统配置
return [
	// 订单系统域名
	'ORDER_DOMAIN' => $order_domain,
	
	// 订单系统接口
	'ORDER_API' => $order_domain.'/api',
	
	// 旧版业务系统域名 
	'OLD_ORDER_DOMAIN' => $old_order_domain,
	
	// 旧版业务系统接口
	'OLD_ORDER_API' => $old_order_domain.'/api.php',
];
