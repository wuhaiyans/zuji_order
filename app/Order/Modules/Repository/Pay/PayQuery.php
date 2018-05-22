<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Pay;

/**
 * 支付单查询
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class PayQuery {
	
	
	//-+------------------------------------------------------------------------
	// | 静态方法方法
	//-+------------------------------------------------------------------------
	/**
	 * 根据业务 获取支付单
	 * @param int		$business_type		业务类型
	 * @param string	$business_no		业务编号
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByBusiness( int $business_type, string $business_no ){
		$info = \App\Order\Models\OrderPayModel::where([
			'business_type'	=> $business_type,
			'business_no'	=> $business_no,
		])->first();
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据业务系统支付编号 获取支付单
	 * @param string	$payment_no		支付编号
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByPaymentNo( string $payment_no ){
		$info = \App\Order\Models\OrderPayModel::where([
			'payment_no'	=> $payment_no,
		])->first();
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据业务系统 代扣协议编号 获取支付单
	 * @param string	$withhold_no		代扣协议编号
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByWithholdNo( string $withhold_no ){
		sql_profiler();
		$info = \App\Order\Models\OrderPayModel::where([
			'withhold_no'	=> $withhold_no,
		])->first();
		var_dump( $info );
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
}
