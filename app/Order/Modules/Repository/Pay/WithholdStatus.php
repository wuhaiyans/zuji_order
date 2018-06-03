<?php
namespace App\Order\Modules\Repository\Pay;

/**
 * 
 */
class WithholdStatus {
	
	const NO_WITHHOLD		= 0;	// 无需签约
	const WAIT_WITHHOLD		= 1;	// 待签约
	const SIGNED			= 2;	// 签约成功
	const UNSIGNING		= 3;	// 解约中
	const UNSIGNED			= 4;	// 已解约
}
