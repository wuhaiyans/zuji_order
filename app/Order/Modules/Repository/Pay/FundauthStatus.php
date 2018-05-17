<?php
namespace App\Order\Modules\Repository\Pay;

/**
 */
class FundauthStatus {
	
	const NO_FUNDAUTH	= 0;	// 无需授权
	const WAIT_FUNDAUTH	= 1;	// 待授权
	const SUCCESS		= 2;	// 授权成功
	const FINISHED		= 3;	// 已完成
	const CLOSED		= 4;	// 已关闭
}
