<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

/**
 * 
 *
 * @author Administrator
 */
class Goods {
	
	/**
	 * 获取商品列表
	 * @param string $good_no		商品编号
	 * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Order\Order
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByOrderNo( string $good_no, int $lock=0 ) {
		return [];
	}
	
	/**
	 * 获取商品
	 * <p>当订单不存在时，抛出异常</p>
	 * @param string	$order_no		订单编号
	 * @param string	$good_no		商品编号
	 * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Order\Order
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByGoodsNo( string $order_no, string $good_no, int $lock=0 ) {
		return new Goods();
		throw new App\Lib\NotFoundException('');
	}

	/**
     * 续租完成
     *      支付完成或创建分期成功执行
     *
     * 步骤:
     *  1.修改商品状态
     *  2.添加新周期
     *  3.修改订单状态
     *  4.解锁订单
     *
     * @author jinlin wang
     * @param array
     * @return boolean
     */
	public static function reletFinish(){
	    //修改商品状态
        //添加新周期
        //修改订单状态
        //解锁订单
        return true;
    }
	
}
