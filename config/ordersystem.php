<?php
// 订单系统系统配置
return [
	// 订单系统接口
	'ORDER_API' => env('ORDER_API', 'http://order.nqyong.com:1081/api'),
	
	// 旧版业务系统接口
	'OLD_ORDER_API' => env('ORDER_API', 'http://biz.nqyong.com:1081/api.php'),
];
