<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

use App\Order\Models\OrderInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\CouponStatus;
use App\Order\Modules\Inc\PayInc;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler;
/**
 * 商品分期
 *
 * @author Administrator
 */
class Instalment {




	/**
	 * 统计商品分期数据
	 * @param array 商品分期二维数组
	 *  [
	 *      'order'=>[
	 *          'order_no'          => 1,//订单编号
	 *      ],
	 *       'sku'=>[
	 *          'zuqi'              => 1,//租期
	 *          'zuqi_type'         => 1,//租期类型
	 *          'all_amount'        => 1,//总金额
	 *          'amount'            => 1,//实际支付金额
	 *          'yiwaixian'         => 1,//意外险
	 *          'zujin'             => 1,//租金
	 *          'pay_type'          => 1,//支付类型
	 *      ],
	 *      'coupon'=>[ 			  // 非必须
	 *          'discount_amount'   => 1,//优惠金额
	 *          'coupon_type'       => 1,//优惠券类型
	 *      ],
	 *      'user'=>[
	 *          'user_id'           => 1,//用户ID
	 *       ],
	 *  ];
	 * @return bool true：成功；false：失败
	 */
	public static function instalmentData( array $data ):bool{


	}

	/**
	 * 创建商品分期
	 * @param array 商品分期二维数组
	 *  [
	 *      'order'=>[
	 *          'order_no'          => 1,//订单编号
	 *      ],
	 *       'sku'=>[
	 *          'zuqi'              => 1,//租期
	 *          'zuqi_type'         => 1,//租期类型
	 *          'all_amount'        => 1,//总金额
	 *          'amount'            => 1,//实际支付金额
	 *          'yiwaixian'         => 1,//意外险
	 *          'zujin'             => 1,//租金
	 *          'pay_type'          => 1,//支付类型
	 *      ],
	 *      'coupon'=>[ 			  // 非必须
	 *          'discount_amount'   => 1,//优惠金额
	 *          'coupon_type'       => 1,//优惠券类型
	 *      ],
	 *      'user'=>[
	 *          'user_id'           => 1,//用户ID
	 *       ],
	 *  ];
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
	 *			'order_no' => '',		// 订单号
	 *			'goods_no' => '',		// 商品编号
	 *			'user_id' => '',		// 用户id
	 *			'term' => '',			// 当前期数
	 *			'times' => '',			// 分期日期
	 * 			'amount' => '',			// 订单号
	 *			'discount_amount' => '',// 优惠金额
	 *			'status' => '',			// 状态： 0：无效；1：未支付；2：扣款成功；3：扣款失败；4：取消；5：扣款中'
	 *			'trade_no' => '',		// 租机交易号
	 *			'out_trade_no' => '',	// 第三方交易号
	 *		]
	 * ]
	 */
	public static function getList( string $goods_no ){
		
	}

	/**
	 * 根据用户id和订单号、商品编号，关闭用户的分期
	 * @param data  array
	 * [
	 *      'id'       => '', 主键ID
	 *      'order_no' => '', 订单编号
	 *      'goods_no' => '', 商品编号
	 * ]
	 * @return boolean	true成功、false失败
	 */
	public static function close( array $data ):bool{
		if (!is_array($data) || $data == [] ) {
			return false;
		}
		$where = [];
		if(isset($data['id'])){
			$where[] = ['id', '=', $data['id']];
		}

		if(isset($data['order_no'])){
			$where[] = ['order_no', '=', $data['order_no']];
		}
		if(isset($data['goods_no'])){
			$where[] = ['goods_no', '=', $data['goods_no']];
		}

		$status = ['status'=>OrderInstalmentStatus::CANCEL];
		$result =  OrderInstalment::where($where)->update($status);
		if (!$result) return false;

		return true;
	}


	public function paySuccess(){

	}


	public function deSuccess(){

	}
	
	
}
