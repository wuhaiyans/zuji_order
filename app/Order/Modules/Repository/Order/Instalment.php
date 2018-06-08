<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

use App\Order\Models\OrderGoodsInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\CouponStatus;
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
	 * @params array 商品分期二维数组
	 *  [
	 *      'order'=>[
	 *          'order_no'          => 1,//订单编号
	 *      ],
	 *       'sku'=>[
	 * 			'goods_no'			=> 1,//商品编号
	 *          'zuqi'              => 1,//租期
	 *          'zuqi_type'         => 1,//租期类型
	 *          'all_amount'        => 1,//总金额
	 *          'amount'            => 1,//实际支付金额
	 *          'insurance'         => 1,//意外险
	 *          'zujin'             => 1,//租金
	 *          'pay_type'          => 1,//支付类型
	 *      ],
	 *      'coupon'=>[ 		// 非必须 二位数组
	 * 			[
	 *          	'discount_amount'   => 1,//优惠金额
	 *          	'coupon_type'       => 1,//优惠券类型
	 *      	]
	 * 		],
	 *      'user'=>[
	 *          'user_id'           => 1,//用户ID
	 *       ],
	 *  ];
	 * @return array 返回分期数据
	 */
	public static function instalmentData( array $params ){

		$filter = self::filter_param($params);
		if(!$filter){
			return false;
		}
		// 单商品分期的相关数据
		$sku = [
			'zujin' 	=> $params['sku']['zujin'],
			'zuqi' 		=> $params['sku']['zuqi'],
			'insurance' => $params['sku']['insurance'],
		];

		// 判断支付方式
		$payment_type_id = $params['sku']['pay_type'];
		$pay_type = [
			\App\Order\Modules\Inc\PayInc::WithhodingPay,
			\App\Order\Modules\Inc\PayInc::MiniAlipay,
		];
		if(!in_array($payment_type_id,$pay_type)){
			return [];
		}

		// 租期类型 长租短租判断
		$zuqi_type = $params['sku']['zuqi_type'];

		// 优惠券
		$coupon = !empty($params['coupon']) ? $params['coupon'] : "";

		// 默认分期
		$model = '\App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter';
		$discount_amount = 0;

		// 优惠券 优惠金额 计算
		if($coupon != ""){
			foreach ($coupon as $item) {
				// 计算优惠金额
				$discount_amount += $item['discount_amount'];

				// 首月零租金判断
				if ($item['coupon_type'] == CouponStatus::CouponTypeFirstMonthRentFree) {
					$model = '\App\Order\Modules\Repository\Instalment\Discounter\FirstDiscounter';
				}
			}
		}
		// 商品优惠金额 递减分期
		$goods_discount_amount = !empty($params['sku']['discount_amount']) ? $params['sku']['discount_amount'] : 0 ;
		if($goods_discount_amount > 0){
			// 递减分期
			$model = '\App\Order\Modules\Repository\Instalment\Discounter\SerializeDiscounter';
		}


		if($zuqi_type == 2){
			// 月租，分期计算器
			$computer = new \App\Order\Modules\Repository\Instalment\MonthComputer( $sku );
		}elseif($zuqi_type == 1){
			// 日租，分期计算器
			$computer = new \App\Order\Modules\Repository\Instalment\DayComputer( $sku );
		}


		// 分期顺序优惠
		$discounter_serialize = new \App\Order\Modules\Repository\Instalment\Discounter\SerializeDiscounter($discount_amount);
		$computer->addDiscounter( $discounter_serialize );


		// 优惠策略
		$discounter = new $model($discount_amount);
		$computer->addDiscounter( $discounter );

		$fenqi_list = $computer->compute();

		return $fenqi_list;
	}

	/**
	 * 创建商品分期
	 * @param array 商品分期二维数组
	 *  [
	 *      'order'=>[
	 *          'order_no'          => 1,//订单编号
	 *      ],
	 *       'sku'=>[
	 * 			'goods_no'			=> 1,//商品编号
	 *          'zuqi'              => 1,//租期
	 *          'zuqi_type'         => 1,//租期类型
	 *          'all_amount'        => 1,//总金额
	 *          'amount'            => 1,//实际支付金额
	 *          'insurance'         => 1,//意外险
	 *          'zujin'             => 1,//租金
	 *          'pay_type'          => 1,//支付类型
	 *      ],
	 *      'coupon'=>[ 		// 非必须 二位数组
	 * 			[
     *          	'discount_amount'   => 1,//优惠金额
     *          	'coupon_type'       => 1,//优惠券类型
     *      	]
	 * 		],
	 *      'user'=>[
	 *          'user_id'           => 1,//用户ID
	 *       ],
	 *  ];
	 * @return bool true：成功；false：失败
	 */
	public static function create( array $params ):bool{
		$filter = self::filter_param($params);
		if(!$filter){
			return false;
		}
		// 调用分期数据
		$_data = self::instalmentData($params);
		if(!$_data){
			\App\Lib\Common\LogApi::error('创建分期错误');
			return false;
		}

		// 判断支付方式
		$payment_type_id = $params['sku']['pay_type'];
		$pay_type = [
			\App\Order\Modules\Inc\PayInc::WithhodingPay,
			\App\Order\Modules\Inc\PayInc::MiniAlipay,
		];
		if(!in_array($payment_type_id,$pay_type)){
			return false;
		}

		$order_no 	= $params['order']['order_no'];
		$goods_no 	= $params['sku']['goods_no'];
		$user_id 	= $params['user']['user_id'];


		// 循环插入
		foreach($_data as &$item){
			$item['order_no'] 			= $order_no;
			$item['goods_no'] 			= $goods_no;
			$item['user_id'] 			= $user_id;
			$item['status']				= \App\Order\Modules\Inc\OrderInstalmentStatus::UNPAID;
			$item['unfreeze_status']	= 2;
			OrderGoodsInstalment::create($item);
		}
		return true;
	}

	// 参数验证
	public function filter_param(array $params):bool{
		if(!is_array($params)){
			return false;
		}

		$order      = $params['order'];
		$sku        = $params['sku'];
		$user       = $params['user'];

		//获取goods_no
		$order = filter_array($order, [
			'order_no'=>'required',
		]);
		if(count($order) < 1){
			return false;
		}

		//获取sku
		$sku = filter_array($sku, [
			'zuqi'          => 'required',
			'zuqi_type'     => 'required',
			'all_amount'    => 'required',
			'amount'        => 'required',
			'insurance'     => 'required',
			'zujin'         => 'required',
			'pay_type'      => 'required',
		]);
		if(count($sku) < 7){
			return false;
		}

		$user = filter_array($user, [
			'user_id' 			=> 'required',
		]);
		if(empty($user)){
			return false;
		}

		return true;
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


		$result =  OrderGoodsInstalment::where($where)->update($status);

		if (!$result) return false;

		return true;
	}

	/**
	 * 支付成功
	 * @param data  array
	 * [
	 *      'reason'       			=> '', //原因
	 *      'status' 				=> '', 【必须】//状态
	 *      'agreement_no' 			=> '', //支付平台签约协议号
	 *      'out_agreement_no'      => '', //业务系统签约协议号
	 *      'trade_no' 				=> '', //支付平台交易码
	 *      'out_trade_no' 			=> '', 【必须】//业务平台交易码
	 * ]
	 * @return String	SUCCESS成功、FAIL失败
	 */
	public static function paySuccess( array $param){

		if($param['status'] == "success"){

			$instalmentInfo = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::getInfo(['id'=>$param['out_trade_no']]);
			if( !is_array($instalmentInfo)){
				// 提交事务
				echo "FAIL";exit;
			}

			$data = [
				'status'        => OrderInstalmentStatus::SUCCESS,
				'update_time'   => time(),
			];

			$b = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::save(['id'=>$param['out_trade_no']], $data);
			if(!$b){
				echo "FAIL";exit;
			}

			// 修改扣款记录数据
			$recordData = [
				'status'        => OrderInstalmentStatus::SUCCESS,
				'update_time'   => time(),
			];
			$record = \App\Order\Modules\Repository\OrderGoodsInstalmentRecordRepository::save(['instalment_id'=>$param['out_trade_no']],$recordData);
			if(!$record){
				echo "FAIL";exit;
			}

			echo "SUCCESS";
		}

		echo "FAIL";exit;

	}

	

}
