<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderGoodsInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\CouponStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
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
	public function instalmentData( array $params ){

		$filter = $this->filter_param($params);
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
	public function create( array $params ):bool{
		$filter = $this->filter_param($params);
		if(!$filter){
			return false;
		}
		// 调用分期数据
		$_data = $this->instalmentData($params);
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
			$item['withhold_day']		= createWithholdDay($item['term'],$item['day']);
			OrderGoodsInstalment::create($item);
		}
		return true;
	}

	// 参数验证
	public function filter_param(array $params){
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

		// 可操作的分期状态
		$statusArr = [OrderInstalmentStatus::UNPAID,  OrderInstalmentStatus::FAIL,  OrderInstalmentStatus::PAYING];

		if(isset($data['id'])){
			$where[] = ['id', '=', $data['id']];
		}

		if(isset($data['order_no'])){
			$where[] = ['order_no', '=', $data['order_no']];
		}

		if(isset($data['goods_no'])){
			$where[] = ['goods_no', '=', $data['goods_no']];
		}

		/**
		 * 根据分期状态 string 或 array 2018/09/15
		 */
		if (isset($data['status']) && !empty($data['status'])) {
			if( is_array($data['status']) ){
				$statusArr = $data['status'];
			}else{
				$statusArr = [$data['status']];
			}
		}

		$status = ['status'=>OrderInstalmentStatus::CANCEL];

        // 查询是否有需要修改的数据
		$orderGoodsInstalment =  OrderGoodsInstalment::query()
			->where($where)
			->whereIn('status',$statusArr)
			->get()->toArray();
		if(!$orderGoodsInstalment){
            return true;
        }

		$result	 =  OrderGoodsInstalment::query()
			->where($where)
			->whereIn('status',$statusArr)
			->update($status);

		if (!$result) return false;

		return true;
	}


	/**
	 * 根据用户id和订单号、商品编号，修改用户的分期为成功
	 * @param data  array
	 * [
	 *      'id'       => '', 主键ID
	 *      'order_no' => '', 订单编号
	 *      'goods_no' => '', 商品编号
	 * ]
	 * @return boolean	true成功、false失败
	 */
	public static function instalmentStatusSuccess( array $data ):bool{
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

		$where[] = ['status', '=', OrderInstalmentStatus::CANCEL];

		//qinliping 2018/08/14  修改
		/**************************/
		$orderGoodsInstalment = OrderGoodsInstalmentRepository::getInfo($where);
		if(!$orderGoodsInstalment){
			return true;
		}
		/************************/

		$status = [
			'status'	=> OrderInstalmentStatus::SUCCESS
		];

		$result =  OrderGoodsInstalment::where($where)->update($status);

		if (!$result) return false;

		return true;
	}


	/**
	 * 代扣扣款成功
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
			LogApi::info("[withhold_paySuccess]代扣定时任务", $param);
			$instalmentInfo = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::getInfo(['business_no'=>$param['out_trade_no']]);
			if( !is_array($instalmentInfo)){
				\App\Lib\Common\LogApi::error('[crontabCreatepay]代扣回调处理分期数据错误');
				return false;
			}


			// 已经处理过的请求 直接返回 true
			if($instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS){
				return true;
			}

//
//			if($instalmentInfo['status'] != OrderInstalmentStatus::PAYING){
//				\App\Lib\Common\LogApi::error('[crontabCreatepay]代扣回调处理分期状态错误');
//				return false;
//			}


			// 查询订单
			$orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($instalmentInfo['order_no']);
			if( !$orderInfo ){
				\App\Lib\Common\LogApi::error('[crontabCreatepay]分期扣款回调-订单信息错误');
				return false;
			}


			/**
			 * 	H5订单查询 订单扣款交易信息
			 */
			if($orderInfo['pay_type'] != \App\Order\Modules\Inc\PayInc::FlowerFundauth && $orderInfo['order_type']  == \App\Order\Modules\Inc\OrderStatus::orderOnlineService){

				// 查询扣款交易
				$withholdData = [
						'trade_no'		=> $param['trade_no'], 			//支付系统交易码
						'out_trade_no'	=> $param['out_trade_no'], 		//业务系统交易码
						'user_id'		=> $instalmentInfo['user_id'], 	//用户id
				];

				$withholdStatus = \App\Lib\Payment\CommonWithholdingApi::deductQuery($withholdData);
				if(!isset($withholdStatus['status']) || $withholdStatus['status'] != 'success'){
					\App\Lib\Common\LogApi::error('[crontabCreatepay]分期扣款回调-扣款交易错误');
					return false;
				}

			}



			$data = [
				'status'        	=> OrderInstalmentStatus::SUCCESS,
				'payment_time'      => time(),
				'update_time'   	=> time(),
				'payment_amount'   	=> $instalmentInfo['amount'],
				'pay_type'   		=> 0,

			];

			// 修改分期状态
			$b = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::save(['business_no'=>$param['out_trade_no']], $data);
			if(!$b){
				\App\Lib\Common\LogApi::error('[crontabCreatepay]修改分期状态失败');
				return false;
			}

			// 创建扣款记录数据
			$recordData = [
				'instalment_id'             => $instalmentInfo['id'],   	// 分期ID
				'status'        			=> OrderInstalmentStatus::SUCCESS,
				'create_time'               => time(),          			// 创建时间
				'update_time'   			=> time(),
			];
			$record = \App\Order\Modules\Repository\OrderGoodsInstalmentRecordRepository::create($recordData);
			if(!$record){
				\App\Lib\Common\LogApi::error('[crontabCreatepay]创建扣款记录失败');
				return false;
			}

			// 创建收支明细
			$incomeData = [
				'name'           => "商品-" . $instalmentInfo['goods_no'] . "分期" . $instalmentInfo['term'] . "代扣",
				'order_no'       => $instalmentInfo['order_no'],
				'business_type'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
				'business_no'    => $param['out_trade_no'],
				'appid'          => \App\Order\Modules\Inc\OrderPayIncomeStatus::WITHHOLD,
				'channel'        => \App\Order\Modules\Repository\Pay\Channel::Alipay,
				'amount'         => $instalmentInfo['amount'],
				'create_time'    => time(),
				'trade_no'       => $param['out_trade_no'],
				'out_trade_no'   => isset($param['trade_no'])?$param['trade_no']:'',
			];
			$incomeB = \App\Order\Modules\Repository\OrderPayIncomeRepository::create($incomeData);
			if(!$incomeB){
				\App\Lib\Common\LogApi::error('[crontabCreatepay]创建收支明细失败');
				return false;
			}


			//发送短信通知 支付宝内部通知
			$notice = new \App\Order\Modules\Service\OrderNotice(
				\App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
				$param['out_trade_no'],
				"InstalmentWithhold");
			$notice->notify();

			// 发送支付宝消息通知
			//$notice->alipay_notify();


		}else if($param['status'] == "failed"){
			// 修改分期状态
			$b = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::save(['business_no'=>$param['out_trade_no']], ['status'=>OrderInstalmentStatus::FAIL]);
			if(!$b){
				\App\Lib\Common\LogApi::error('[crontabCreatepay]修改分期状态失败');
				return false;
			}
		}

		return true;


	}


	/**
	 * 线下还款成功
	 * @param data  array
	 * [
	 *      'instalment_id'       			=> '', //分期id
	 * ]
	 * @return String	SUCCESS成功、FAIL失败
	 */
	public static function underLinePaySuccess( int $instalment_id){


		LogApi::debug("[underLinePaySuccess]线下分期还款", $instalment_id);

		$instalmentInfo = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::getInfoById($instalment_id);
		if( !is_array($instalmentInfo)){
			LogApi::error('[underLinePaySuccess]线下分期还款分期查询错误_'.$instalment_id);
			return false;
		}

		$business_no = createNo();
		$data = [
			'business_no'		=> $business_no,	//设置交易号 为发送短信根据 business_no  查询分期
			'status'        	=> OrderInstalmentStatus::SUCCESS,
			'payment_time'      => time(),
			'update_time'   	=> time(),
			'payment_amount'   	=> $instalmentInfo['amount'],
			'pay_type'   		=> OrderInstalmentStatus::UNDERLINE,

		];
		// 修改分期状态
		$b = \App\Order\Modules\Repository\OrderGoodsInstalmentRepository::save(['id' => $instalment_id], $data);
		if(!$b){
			LogApi::error('[underLinePaySuccess]线下分期还款-修改分期状态失败');
			return false;
		}


		//发送短信通知 支付宝内部通知
		$notice = new \App\Order\Modules\Service\OrderNotice(
			\App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
			$business_no,
			"InstalmentWithhold");
		$notice->notify();

		return true;


	}
	

}
