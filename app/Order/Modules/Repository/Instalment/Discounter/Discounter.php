<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Instalment\Discounter;

/**
 * 优惠 接口
 *
 * @author 
 */
interface Discounter{
	
	
	/**
	 * 优惠计算
	 * @param array $params		分期列表参数（二维数组）
	 * [
	 *		[
	 *			'term'			=> '',//【必选】int 期(yyyymm)
	 *			'times'			=> '',//【必选】int 第几期
	 *			'day'			=> '',//【必选】int 扣款日
	 *			'original_amount'	=> '',//【必选】price 每期金额
	 *			'discount_amount'	=> '',//【必选】price 每期优惠金额
	 *			'amount'			=> '',//【必选】price 每期应还金额
	 *		]
	 * ]
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function discount( array $params );
	
}
