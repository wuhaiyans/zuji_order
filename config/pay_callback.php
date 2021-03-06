<?php

// 支付阶段完成时业务的回调配置
// 回调：
// 接收第一个参数，关联数组类型：
// [
//		'business_type' => '',	// 业务类型
//		'business_no'	=> '',	// 业务编码
//		'status'		=> '',	// 支付状态  processing：处理中；success：支付完成
// ]
// 接收第二个参数，关联数组类型：
// [
//		'uid'		=> '',	// 用户ID
//		'username'	=> '',	// 用户名
//		'type'		=> '',	// 用户类型
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
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI => '\App\Order\Modules\Service\OrderPayNotify::callback',
		// 业务类型为1的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN => 'var_dump',
		// 业务类型为1的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_BARTER => 'var_dump',
		// 业务类型为【还机】4的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK => '\App\Order\Modules\Service\OrderGiveback::callbackPayment',
		// 业务类型为【买断】的支付回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT => '\App\Order\Modules\Service\OrderBuyout::callbackPaid',

        // 业务类型为【续租】6的支付回调通知
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_RELET => '\App\Order\Modules\Service\OrderRelet::callback',

		// 提前还款业务回调
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI=> '\App\Order\Modules\Service\OrderWithhold::repaymentNotify',

        //预定业务 支付回调通知
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_DESTINE => '\App\Activity\Modules\Service\DestinePayNotify::callback',

        //体验活动业务 支付回调通知
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_EXPERIENCE => '\App\Activity\Modules\Service\ExperiencePayNotify::callback',
	],

	'refund' => [
		// 退款 业务类型 回调
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_REFUND => '\App\Order\Modules\Service\OrderReturnCreater::refundUpdate',
		// 退货 业务类型 回调
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN => '\App\Order\Modules\Service\OrderReturnCreater::refundUpdate',
		// 业务类型为【还机】4的清算回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK => '\App\Order\Modules\Service\OrderGiveback::callbackClearing',
		// 业务类型为【买断】4的清算回调通知
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT => '\App\Order\Modules\Service\OrderBuyout::callbackOver',
        // 业务类型为【预约】的回调通知
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_DESTINE => '\App\Activity\Modules\Service\Appointment::callbackAppointment',
        // 业务类型为【1元活动】的回调通知
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_EXPERIENCE => '\App\Activity\Modules\Service\ActivityExperience::callbackExperience',
	],
];
