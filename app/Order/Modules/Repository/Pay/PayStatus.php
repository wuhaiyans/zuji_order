<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Pay;

/**
 * Description of PayStatus
 *
 * @author Administrator
 */
class PayStatus {
	
	const UNKNOWN			= 0;	// 无效
	const WAIT_PAYMENT		= 1;	// 待支付
	const WAIT_WHITHHOLD	= 2;	// 待代扣签约
	const WAIT_FUNDAUTH		= 3;	// 待资金授权
	const SUCCESS		= 4;	// 已完成
	const CLOSED		= 5;	// 已关闭
}
