<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderGivebackRepository;
use App\Order\Modules\Inc\OrderGivebackStatus;
use Illuminate\Support\Facades\DB;

class OrderGiveback
{
	/**
	 * 订单还机数据处理仓库
	 * @var obj
	 */
	protected $order_giveback_repository;
	public function __construct(  ) {
		$this->order_giveback_repository = new OrderGivebackRepository();
	}
    /**
     * 保存还机单数据
     * @param $data
     * @return id
     */
    public function create($data){
		$data = filter_array($data, [
			'order_no' => 'required',//订单编号
			'goods_no' => 'required',//商品编号
			'user_id' => 'required',//用户id
			'logistics_id' => 'required',//物流类型
			'logistics_name' => 'required',//物流名称
			'logistics_no' => 'required',//物流单号
			'giveback_no' => 'required',//还机单编号
			'status' => 'required',//订单状态
		]);

		if( count($data)!=8 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92100, '还机单创建：必要参数缺失!');
			return false;
		}
		$data['create_time'] = $data['update_time'] = time();
        $result = $this->order_giveback_repository->create( $data );
		if( !$result ) {
			set_code(\App\Lib\ApiStatus::CODE_92201);
		}
		return $result;
    }
    /**
     * 根据商品编号获取一条还机单数据
	 * @param string $goodsNo 商品编号
	 * @return array|false
	 */
	public function getInfoByGoodsNo( $goodsNo ) {
		if( empty($goodsNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取还机单数据时订单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getInfoByGoodsNo($goodsNo);
	}
    /**
     * 根据还机编号获取一条还机单数据
	 * @param string $givebackNo 还机编号
	 * @return array|false
	 */
	public function getInfoByGivabackNo( $givebackNo ) {
		if( empty($givebackNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取还机单数据时还机单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getInfoByGivabackNo($givebackNo);
	}
    /**
     * 获取当前订单下所有未完成的还机单
	 * @param string $orderNo 订单编号
	 * @return array|false
	 */
	public function getUnfinishedListByOrderNo( $orderNo ) {
		if( empty($orderNo) ) {
			set_apistatus(\App\Lib\ApiStatus::CODE_92300,'获取未完成的还机单列表：订单编号参数为空!');
			return false;
		}
		return $this->order_giveback_repository->getUnfinishedListByOrderNo( $orderNo );
	}
	
    /**
     * 根据条件更新数据
	 * @param array $where 更新条件【至少含有一项条件】
	 * $where = [<br/>
	 *		'goods_no' => '',//商品编号<br/>
	 * ]<br/>
	 * @param array $data 需要更新的数据 【至少含有一项数据】
	 * $data = [<br/>
	 *		'status'=>'',//还机状态<br/>
	 *		'withhold_status'=>'',//代扣状态<br/>
	 *		'instalment_num'=>'',//剩余还款的分期数<br/>
	 *		'instalment_amount'=>'',//剩余还款的分期总金额（分）<br/>
	 *		'payment_status'=>'',//支付状态 0默认<br/>
	 *		'payment_time'=>'',//支付时间<br/>
	 *		'logistics_id'=>'',//物流类型<br/>
	 *		'logistics_name'=>'',//物流名称<br/>
	 *		'logistics_no'=>'',//物流编号<br/>
	 *		'evaluation_status'=>'',//检测结果<br/>
	 *		'evaluation_remark'=>'',//检测备注<br/>
	 *		'evaluation_time'=>'',//检测时间<br/>
	 *		'yajin_status'=>'',//押金退还状态<br/>
	 *		'compensate_amount'=>'',//赔偿金额<br/>
	 *		'remark'=>'',//备注<br/>
	 * ]
	 */
	public function update( $where, $data ) {
		$where = filter_array($where, [
			'goods_no' => 'required',
		]);
		$data = filter_array($data, [
			'status' => 'required',
			'withhold_status' => 'required',
			'instalment_num' => 'required',
			'instalment_amount' => 'required',
			'payment_status' => 'required',
			'payment_time' => 'required',
			'logistics_id' => 'required',
			'logistics_name' => 'required',
			'logistics_no' => 'required',
			'evaluation_status' => 'required',
			'evaluation_remark' => 'required',
			'evaluation_time' => 'required',
			'compensate_amount' => 'required',
			'remark' => 'required',
		]);
		if( count( $where ) < 1 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92600,'还机单修改：条件参数为空');
			return false;
		}
		if( count( $data ) < 1 ){
			set_apistatus(\App\Lib\ApiStatus::CODE_92600,'还机单修改：数据参数为空');
			return false;
		}
		$data['update_time'] = time();
		return $this->order_giveback_repository->update( $where, $data );
	}
	/**
	 * 还机单清算完成回调接口
	 * @param array $params 还机单清算完成回调参数<br/>
	 * $params = [
	 *		'business_type' => '',//业务类型【必须是还机业务】
	 *		'business_no' => '',//业务编码【必须是还机单编码】
	 *		'status' => '',//支付状态  processing：处理中；success：支付完成
	 * ]
	 */
	public static function callbackClearing( $params ) {
		//参数过滤
        $rules = [
            'business_type'     => 'required',//业务类型
            'business_no'     => 'required',//业务编码
            'status'     => 'required',//支付状态
        ];
        $validator = app('validator')->make($params, $rules);
        if ($validator->fails()) {
			set_apistatus(ApiStatus::CODE_91000, $validator->errors()->first());
			return false;
        }
		//清算成功
		if( $params['status'] != 'success' || $params['business_type'] != \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK ){
			set_apistatus(ApiStatus::CODE_91000, '状态值或业务类型有误!');
			return false;
		}
		
		//创建服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGivebackService = new OrderGiveback();
		//获取还机单信息
		$orderGivevbackInfo = $orderGivebackService->getInfoByGivabackNo($params['business_no']);
		if( !$orderGivevbackInfo ) {
			return false;
		}
		
		//-+--------------------------------------------------------------------
		// | 更新订单状态（交易完成）
		//-+--------------------------------------------------------------------
//		//开启事务
//		DB::beginTransaction();
		try{
			$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$params['business_no']], [
				'status'=> OrderGivebackStatus::STATUS_DEAL_DONE,
				'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION,
			]);
			if( !$orderGivebackResult ){
//				//事务回滚
//				DB::rollBack();
				return false;
			}
			$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$orderGivevbackInfo['goods_no']], ['status'=> OrderGivebackStatus::STATUS_DEAL_DONE]);
			if( !$orderGoodsResult ){
//				//事务回滚
//				DB::rollBack();
				return false;
			}
		} catch (\Exception $ex) {
//			//事务回滚
//			DB::rollBack();
			return false;
		}
//		//事务提交
//		DB::commit();
		return true;
	}
	
	/**
	 * 还机单支付完成回调接口
	 * @param Request $request
	 */
	public static function callbackPayment( $params ) {
		//参数过滤
        $rules = [
            'business_type'     => 'required',//业务类型
            'business_no'     => 'required',//业务编码
            'status'     => 'required',//支付状态
        ];
        $validator = app('validator')->make($params, $rules);
        if ($validator->fails()) {
			set_apistatus(ApiStatus::CODE_91000, $validator->errors()->first());
			return false;
        }
		//清算成功
		if( $params['status'] != 'success' || $params['business_type'] != \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK ){
			set_apistatus(ApiStatus::CODE_91000, '状态值或业务类型有误!');
			return false;
		}
		//创建服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGivebackService = new OrderGiveback();
		//获取还机单信息
		$orderGivevbackInfo = $orderGivebackService->getInfoByGivabackNo($params['business_no']);
		if( !$orderGivevbackInfo ) {
			return false;
		}
		//获取商品信息
		$orderGoodsInfo = $orderGoodsService->getGoodsInfo($orderGivevbackInfo['goods_no']);
		if( !$orderGoodsInfo ) {
			return false;
		}
		try{
			//-+--------------------------------------------------------------------
			// | 判断订单押金，是否生成清算单
			//-+--------------------------------------------------------------------

			//-+--------------------------------------------------------------------
			// | 不生成=》更新订单状态（交易完成）
			//-+--------------------------------------------------------------------
			if( $orderGoodsInfo['yajin'] == 0 ){
				$status = OrderGivebackStatus::STATUS_DEAL_DONE;
				$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$params['business_no']], [
					'status'=> $status,
					'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN,
					'payment_status'=> OrderGivebackStatus::PAYMENT_STATUS_ALREADY_PAY,
				]);
				if( !$orderGivebackResult ){
					return false;
				}
			}
			//-+--------------------------------------------------------------------
			// | 生成=>更新订单状态（处理中，待清算）
			//-+--------------------------------------------------------------------
			else{
				$status = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
				$orderGivebackResult = $orderGivebackService->update(['giveback_no'=>$params['business_no']], [
					'status'=> $status,
					'yajin_status'=> OrderGivebackStatus::YAJIN_STATUS_IN_RETURN,
					'payment_status'=> OrderGivebackStatus::PAYMENT_STATUS_ALREADY_PAY,
				]);
				if( !$orderGivebackResult ){
					return false;
				}
				//获取当时订单支付时的相关pay的对象信息【查询payment_no和funath_no】
				$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,$orderGoodsInfo['order_no'] );
				//清算处理数据拼接
				$clearData = [
					'user_id' => $orderGivebackInfo['user_id'],
					'order_no' => $orderGivebackInfo['order_no'],
					'business_type' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
					'bussiness_no' => $orderGivebackInfo['giveback_no'],
					'auth_deduction_amount' => 0,//扣除押金金额
					'auth_unfreeze_amount' => $orderGoodsInfo['yajin'],//退还押金金额
					'payment_no' => $payObj->getPaymentNo(),//payment_no
					'fundauth_no' => $payObj->getFundauthNo(),//和funath_no
				];
				//进入清算处理
				$orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
				if( !$orderCleanResult ){
					set_apistatus(ApiStatus::CODE_93200, '押金退还清算单创建失败!');
					return false;
				}
			}
			$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$orderGivevbackInfo['goods_no']], ['status'=> $status]);
			if( !$orderGoodsResult ){
				return false;
			}
		} catch (\Exception $ex) {
			return false;
		}
		return true;
	}
}
