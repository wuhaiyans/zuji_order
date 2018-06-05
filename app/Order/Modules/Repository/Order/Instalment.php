<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

/**
 * 商品分期
 *
 * @author Administrator
 */
class Instalment {
	
	/**
	 * 创建商品分期
	 * @param array	商品分期二维数组
	 * [
	 *		[
	 *			'' => '',
	 *			'' => '',
	 *			'' => '',
	 *			'' => '',
	 *			'' => '',
	 *		]
	 * ]
	 * @return bool true：成功；false：失败
	 */
	public static function create( array $data ):bool{
		
	}
	
	/**
	 * 读取商品分期
	 * @param string $goods_no	商品编号
	 * @return array	商品分期二维数组
	 * [
	 *		[
	 *			'' => '',
	 *			'' => '',
	 *			'' => '',
	 *			'' => '',
	 *			'' => '',
	 *		]
	 * ]
	 */
	public static function getList( string $goods_no ){
		
	}
	
	
}
