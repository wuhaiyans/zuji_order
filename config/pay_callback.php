
<?php

// 支付阶段完成时业务的回调配置
// 回调接收一个参数，关联数组类型：
// [
//		'business_type' => '',	// 业务类型
//		'business_no'	=> '',	// 业务编码
//		'status'		=> '',	// 支付状态  processing：处理中；success：支付完成
// ]
// 支付阶段分3个环节，一次是：直接支付 -> 代扣签约 -> 资金预授权
// 所有业务回调有可能收到两种通知：
//	1）status 为 processing
//		这种情况时
//		表示：直接支付环节已经完成，还有后续环节没有完成。
//		要求：如果这时要取消支付后，必须进行退款处理，然后才可以关闭业务。
//	2）status 为 success
// 格式： 键：业务类型；值：可调用的函数，类静态方法
return [

	'payment' => [
		// 业务类型为租机的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI => '\App\Order\Modules\Service\OrderPayNotify\callback',
		// 业务类型为1的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN => 'var_dump',
		// 业务类型为1的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER => 'var_dump',
		// 业务类型为1的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK => 'var_dump',
		// 业务类型为1的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT => 'var_dump',

		\App\Order\Modules\Inc\OrderStatus::BUSINESS_RELET=>''
	],

	'refund' => [
		// 业务类型为1的支付回调通知
		'2' => '\App\Lib\Refund\Refund\refundUpdate'

	],
];
