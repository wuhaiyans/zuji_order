<?php
// 支付阶段完成时业务的回调配置【后台操作，不和用户产生交互的配置】
return [
	//【还机】还机的代扣支付回调地址
	'giveback_withhlod' => '/web/order/giveback/callbackWithhold',
];
