<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

/**
 * 发货明细
 * （IMEI，合同）
 */
class DeliveryDetail {
	
	/**
	 * 发货明细
	 * @param string $goods_no
	 * @return array|bool	array：发货明细（一维数组）；false：不存在
	 * [
	 * ]
	 */
	public static function getDetail( string $goods_no ){
		return false;
	}
	
	/**
	 * 发货明细列表
	 * @param string $order_no
	 * @return array	发货明细列表（二维数组）
	 * [
	 * ]
	 */
	public static function getDetailList( string $order_no ){
		return [];
	}
	
}
