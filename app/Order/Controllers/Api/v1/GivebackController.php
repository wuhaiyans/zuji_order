<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Service\OrderGoods;
use App\Order\Modules\Service\OrderInstalment;
use App\Order\Modules\Service\OrderWithhold;
use App\Order\Modules\Inc\OrderInstalmentStatus;

class GivebackController extends Controller
{
	/**
	 * 获取还机申请中页面数据
	 * @param Request $request
	 * @return type
	 */
	public function getApplyingViewdata( Request $request ) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :'';
		if( empty($paramsArr['goods_no']) ) {
            return apiResponse([],ApiStatus::CODE_91001);
		}
		$goodsNo = $paramsArr['goods_no'];//提取商品编号
		//-+--------------------------------------------------------------------
		// | 通过商品编号获取需要展示的数据
		//-+--------------------------------------------------------------------
		
		//初始化最终返回数据数组
		$data = [];
		//获取商品基础数据
		//创建商品服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
		if( !$orderGoodsInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		//组合最终返回商品基础数据
		$data['goods_no'] = $orderGoodsInfo['goods_no'];//商品编号
		$data['goods_name'] = $orderGoodsInfo['goods_name'];
		$data['goods_thumb'] = $orderGoodsInfo['goods_thumb'];
		$data['status'] = OrderGivebackStatus::getStatusName(OrderGivebackStatus::STATUS_APPLYING);
		$data['zuqi'] = $orderGoodsInfo['zuqi'];
		$data['zuqi_type'] = $orderGoodsInfo['zuqi_type'];
		$data['zuqi_begin_date'] = $orderGoodsInfo['begin_time'];
		$data['zuqi_end_date'] = $orderGoodsInfo['end_time'];
		$data['order_no'] = $orderGoodsInfo['order_no'];
		
		//默认不需要展示已支付和待支付租金价格字段
		$data['zujin_view_flag'] = 0;
		//判断商品租期类型【长租代扣支付需要获取分期】
		if( $orderGoodsInfo['zuqi_type'] == 1 ){
			return apiResponse($data,ApiStatus::CODE_0,'数据获取成功');
		}
		//获取当前商品是否存在分期列表
		$instalmentList = OrderInstalment::queryList(['goods_no'=>$goodsNo], ['limit'=>36,'page'=>1]);
		if( empty($instalmentList[$goodsNo]) ){
			return apiResponse($data,ApiStatus::CODE_0,'数据获取成功');
		}
		
		//长租代扣分期，展示已支付租金和待支付租金
		$data['zujin_view_flag'] = 1;
		$zujinAlreadyPay = $instalmentAmount = 0;
		foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
			if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::PAYING, OrderInstalmentStatus::SUCCESS]) ) {
				$zujinAlreadyPay += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
			}
			if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
				$instalmentAmount += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
			}
		}
		//组合最终返回价格基础数据
		$data['zujin_already_pay'] = $zujinAlreadyPay;
		$data['zujin_need_pay'] = $instalmentAmount;
		return apiResponse($data,ApiStatus::CODE_0,'数据获取成功');
	}
	/**
	 * 生成还机单等相关操作
	 * @param Request $request
	 * @return type
	 */
	public function create( Request $request ) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//商品编号
            'order_no'     => 'required',//订单编号
            'user_id'     => 'required',//用户id
            'logistics_no'     => 'required',//物流单号
            'logistics_id'     => 'required',//物流id
            'logistics_name'     => 'required',//物流名称
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
		$goodsNoArr = is_array($paramsArr['goods_no']) ? $paramsArr['goods_no'] : [$paramsArr['goods_no']];
		//-+--------------------------------------------------------------------
		// | 业务处理：冻结订单、生成还机单、推送到收发货系统【加事务】
		//-+--------------------------------------------------------------------
		//开启事务
		DB::beginTransaction();
		try{
			foreach ($goodsNoArr as $goodsNo) {
				//生成还机单编号
				$paramsArr['giveback_no'] = $giveback_no = createNo(7);
				//初始化还机单状态
				$paramsArr['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY;
				$paramsArr['goods_no'] = $goodsNo;
				//生成还机单
				$orderGivebackService = new OrderGiveback();
				$orderGivebackIId = $orderGivebackService->create($paramsArr);
				if( !$orderGivebackIId ){
					//事务回滚
					DB::rollBack();
					return apiResponse([], get_code(), get_msg());
				}
				//修改商品表业务类型、商品编号、还机状态
				$orderGoodsService = new OrderGoods();
				$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$paramsArr['goods_no']], [
					'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
					'business_no' => $giveback_no,
					'goods_status' => $status,
				]);
				if(!$orderGoodsResult){
					//事务回滚
					DB::rollBack();
					return apiResponse([], get_code(), get_msg());
				}
				//推送到收发货系统
				$warehouseResult = \App\Lib\Warehouse\Receive::create($paramsArr['order_no'], \App\Order\Modules\Inc\OrderStatus::BUSINESS_RETURN, [['goods_no'=>$goodsNo]]);
				if( !$warehouseResult ){
					//事务回滚
					DB::rollBack();
					return apiResponse([], ApiStatus::CODE_93200, '收货单创建失败!');
				}
			}
			//冻结订单
			$orderFreezeResult = \App\Order\Modules\Repository\OrderRepository::orderFreezeUpdate($paramsArr['order_no'], \App\Order\Modules\Inc\OrderFreezeStatus::Reback);
			if( !$orderFreezeResult ){
				return apiResponse([],ApiStatus::CODE_92700,'订单冻结失败！');
			}
		} catch (\Exception $ex) {
			//事务回滚
			DB::rollBack();
			return apiResponse([],ApiStatus::CODE_94000,$ex->getMessage());
		}
		//提交事务
		DB::commit();
		return apiResponse([],ApiStatus::CODE_0,'归还设备申请提交成功');
	}
	/**
	 * 还机确认收货
	 * @param Request $request
	 */
	public function confirmDelivery( Request $request ) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//商品编号
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
		$goodsNo = $paramsArr['goods_no'];
		//-+--------------------------------------------------------------------
		// | 业务处理：获取判断当前还机单状态、更新还机单状态
		//-+--------------------------------------------------------------------
		//获取还机单信息
		$orderGivebackService = new OrderGiveback();//创建还机单服务层
		$orderGoodsInfo = $orderGivebackService->getInfoByGoodsNo($goodsNo);
		//还机单状态必须为待收货
		if( !$orderGoodsInfo ){
            return apiResponse([], get_code(), get_msg());
		}
		if( $orderGoodsInfo['status'] == OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK ){
            return apiResponse([],ApiStatus::CODE_92500,'当前还机单已经收货');
		}
		if( $orderGoodsInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY ) {
            return apiResponse([],ApiStatus::CODE_92500,'当前还机单不处于待收货状态，不能进行收货操作');
		}
		//开启事务
		DB::beginTransaction();
		try{
			//-+------------------------------------------------------------------------------
			// |收货时：查询未完成分期直接进行代扣，并记录代扣状态
			//-+------------------------------------------------------------------------------
			//获取当前商品未完成分期列表数据
			$instalmentList = OrderInstalment::queryList(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
			if( !empty($instalmentList[$goodsNo]) ){
				foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
					OrderWithhold::instalment_withhold($instalmentInfo['id']);
				}
				//代扣已执行
				$withhold_status = OrderGivebackStatus::WITHHOLD_STATUS_ALREADY_WITHHOLD;
			} else {
				//无需代扣
				$withhold_status = OrderGivebackStatus::WITHHOLD_STATUS_NO_NEED_WITHHOLD;
			}
			
			//更新还机单状态到待收货
			$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], [
				'status' => OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK,
				'withhold_status' => $withhold_status,
			]);
			if( !$orderGivebackResult ){
				//事务回滚
				DB::rollBack();
				return apiResponse([],ApiStatus::CODE_92701);
			}
		} catch (\Exception $ex) {
			//事务回滚
			DB::rollBack();
			return apiResponse([],ApiStatus::CODE_94000,$ex->getMessage());
		}
		//提交事务
		DB::commit();
		return apiResponse([],ApiStatus::CODE_0,'确认收货成功');
	}
	
	/**
	 * 还机确认收货结果
	 * @param Request $request
	 */
	public function confirmEvaluation( Request $request ) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//商品编号
            'evaluation_status'     => 'required',//检测状态【1：合格；2：不合格】
            'evaluation_time'     => 'required',//检测时间
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
		if( !in_array($paramsArr['evaluation_status'], [OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED,OrderGivebackStatus::EVALUATION_STATUS_QUALIFIED])  ){
            return apiResponse([],ApiStatus::CODE_91000,'检测状态参数值错误!');
		}
		if( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && (empty($paramsArr['evaluation_remark']) || empty($paramsArr['compensate_amount'])) ){
            return apiResponse([],ApiStatus::CODE_91000,'检测不合格时：检测备注和赔偿金额均不能为空!');
		}
		$paramsArr['compensate_amount'] = isset($paramsArr['compensate_amount'])? floatval($paramsArr['compensate_amount']):0;
		$paramsArr['evaluation_remark'] = isset($paramsArr['evaluation_remark'])?strval($paramsArr['evaluation_remark']):'';
		$goodsNo = $paramsArr['goods_no'];//商品编号提取
		
		//-+--------------------------------------------------------------------
		// | 业务处理
		//-+--------------------------------------------------------------------
		
		//创建商品服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGivebackService = new OrderGiveback();
		//-+--------------------------------------------------------------------
		// | 业务处理：判断是否需要支付【1有无未完成分期，2检测不合格的赔偿】
		//-+--------------------------------------------------------------------
		//获取商品信息
		$orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
		if( !$orderGoodsInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		//获取还机单信息
		$orderGivevbackInfo = $orderGivebackService->getInfoByGoodsNo($goodsNo);
		if( !$orderGivevbackInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		
		//获取当前商品未完成分期列表数据
		$instalmentList = OrderInstalment::queryList(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
		//剩余分期需要支付的总金额、还机需要支付总金额
		$instalmentAmount = $givebackNeedPay = 0;
		//剩余分期数
		$instalmentNum = 0;
		if( !empty($instalmentList[$goodsNo]) ){
			foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
				if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
					$instalmentAmount += $instalmentInfo['amount'];
					$instalmentNum++;
				}
			}
		}
		
		//拼接相关参数到paramsArr数组
		$paramsArr['order_no'] = $orderGivevbackInfo['order_no'];//订单编号
		$paramsArr['user_id'] = $orderGivevbackInfo['user_id'];//用户id
		$paramsArr['giveback_no'] = $orderGivevbackInfo['giveback_no'];//还机单编号
		
		$paramsArr['instalment_num'] = $instalmentNum;//需要支付的分期的期数
		$paramsArr['instalment_amount'] = $instalmentAmount;//需要支付的分期的金额
		$paramsArr['yajin'] = $orderGoodsInfo['yajin'];//押金金额
		
		//开启事务
		DB::beginTransaction();
		try{
			//存在未完成分期单，关闭分期单
			$instalmentResult = true;
			if( $instalmentNum ){
				$instalmentResult = OrderInstalment::close(['goods_no'=>$goodsNo]);
			}
			//分期关闭失败，回滚
			if( !$instalmentResult ) {
				DB::rollBack();
				return apiResponse([], ApiStatus::CODE_92700, '订单分期关闭失败!');
			}
			//-+----------------------------------------------------------------
			// | 检测合格-代扣成功(无剩余分期)
			//-+----------------------------------------------------------------
			if( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_QUALIFIED && !$instalmentNum ){
				$dealResult = $this->__dealEvaYesWitYes($paramsArr, $orderGivebackService, $status);
			}
			//-+----------------------------------------------------------------
			// | 检测合格-代扣不成功(有剩余分期)
			//-+----------------------------------------------------------------
			elseif ( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_QUALIFIED && $instalmentNum ) {
				$dealResult = $this->__dealEvaYesWitNo($paramsArr, $orderGivebackService, $status);
			}
			
			//-+----------------------------------------------------------------
			// | 检测不合格-代扣成功(无剩余分期)
			//-+----------------------------------------------------------------
			
			elseif ( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && !$instalmentNum ) {
				$dealResult = $this->__dealEvaNoWitYes($paramsArr, $orderGivebackService, $status);
			}
			
			//-+----------------------------------------------------------------
			// | 检测不合格-代扣不成功(有剩余分期)
			//-+----------------------------------------------------------------
			elseif ( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && $instalmentNum ) {
				$dealResult = $this->__dealEvaNoWitNo($paramsArr, $orderGivebackService, $status);
			}
			//-+----------------------------------------------------------------
			// | 不应该出现的结果，直接返回错误
			//-+----------------------------------------------------------------
			else {
				throw new \Exception('这简直就是一个惊天大bug，天上有漏洞----->你需要一个女娲—.—');
			}
			
//			//初始化还机单需要更新的数据
//			$data = [
//				'instalment_num' => $instalmentNum,
//				'instalment_amount' => $instalmentAmount,
//				'evaluation_status' => $paramsArr['evaluation_status'],
//				'evaluation_remark' => $paramsArr['evaluation_remark'],
//				'evaluation_time' => $paramsArr['evaluation_time'],
//				'compensate_amount' => $paramsArr['compensate_amount'],
//			];
//
//			//需要支付金额和押金均为0时，直接修改还机单和商品单状态
//			if( $givebackNeedPay == 0 && $yajin == 0 ) {
//				//解冻订单
//				//查询当前订单处于还机未结束的订单数量（大于1则不能解冻订单）
//				$givebackUnfinshedList = $orderGivebackService->getUnfinishedListByOrderNo($orderGivevbackInfo['order_no']);
//				if( count($givebackUnfinshedList) == 1 ){
//					$orderFreezeResult = \App\Order\Modules\Repository\OrderRepository::orderFreezeUpdate($orderGivevbackInfo['order_no'], \App\Order\Modules\Inc\OrderFreezeStatus::Non);
//					if( !$orderFreezeResult ){
//						//事务回滚
//						DB::rollBack();
//						return apiResponse([], ApiStatus::CODE_92700, '订单解冻失败!');
//					}
//				} 
//				//修改还机单状态
//				$data['status'] = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_DONE;
//				$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
//				$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN;
//				$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], $data);
//			}
//			//需要支付总金额小于等于押金金额：交由清算处理
//			elseif( $givebackNeedPay <= $yajin ){
//				//清算处理
//				$clearData = [
//					'user_id' => $orderGivevbackInfo['user_id'],
//					'order_no' => $orderGivevbackInfo['order_no'],
//					'business_type' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
//					'bussiness_no' => $orderGivevbackInfo['giveback_no'],
//					'auth_deduction_amount' => ''.$givebackNeedPay,//扣除押金金额
//					'auth_unfreeze_amount' => ''.( $yajin - $givebackNeedPay ),//退还押金金额
//				];
//				$orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
//				if( !$orderCleanResult ){
//					//事务回滚
//					DB::rollBack();
//					return apiResponse([], ApiStatus::CODE_93200, '押金退还清算单创建失败!');
//				}
//				//更新还机单状态
//				$data['status'] = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
//				$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
//				$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_ON_RETURN;
//				$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], $data);
//			}
//			//需要支付总金额大于押金金额：交由支付处理，在支付回调验证押金进行清算处理
//			else{
//				$payData = [
//					'businessType' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,// 业务类型 
//					'businessNo' => $orderGivevbackInfo['giveback_no'],// 业务编号
//					'paymentAmount' => $givebackNeedPay,// Price 支付金额，单位：元
//					'paymentFenqi' => 0,//不分期
//				];
//				$payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payData);
//				if( !$payResult->isSuccess() ){
//					//事务回滚
//					DB::rollBack();
//					return apiResponse([], ApiStatus::CODE_93200, '支付单创建失败!');
//				}
//				//更新还机单状态
//				$data['status'] = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
//				$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
//				$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], $data);
//			}
			
			//更新还机表状态失败回滚
			if( !$dealResult ){
				DB::rollBack();
				return apiResponse([], get_code(), get_msg());
			}
			//更新商品表状态
			$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$goodsNo], ['goods_status'=>$status]);
			if( !$orderGoodsResult ){
				//事务回滚
				DB::rollBack();
				return apiResponse([], get_code(), get_msg());
			}
		} catch (\Exception $ex) {
			//回滚事务
			DB::rollBack();
			return apiResponse([], ApiStatus::CODE_94000, $ex->getMessage());
		}
		//提交事务
		DB::commit();
		return apiResponse([], ApiStatus::CODE_0, '成功');
	}
	
	/**
	 * 获取支付信息
	 * @param Request $request
	 * @return type
	 */
	public function getPaymentInfo( Request $request ) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//还机单编号
            'callback_url'     => 'required',//回调地址
            'channel_id'     => 'required',//支付的渠道id
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
		//创建商品服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGivebackService = new OrderGiveback();
		//获取还机单基本信息
		$orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($paramsArr['goods_no']);
		if( !$orderGivebackInfo ){
            return apiResponse([], get_code(), get_msg());
		}
		//获取商品信息
		$orderGoodsInfo = $orderGoodsService->getGoodsInfo($orderGivebackInfo['goods_no']);
		if( !$orderGoodsInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		try{
			//获取支付的url
			$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,$orderGivebackInfo['giveback_no'] );
			$paymentUrl = $payObj->getCurrentUrl($paramsArr['channel_id'], [
				'name'=>'订单' .$orderGoodsInfo['order_no']. '设备'.$orderGivebackInfo['goods_no'].'还机支付',
				'front_url' => $paramsArr['callback_url'],
			]);
		} catch (\Exception $ex) {
			return apiResponse([], ApiStatus::CODE_94000,$ex->getMessage());
		}
		//拼接返回数据
		$data = [
			'order_no' => $orderGoodsInfo['order_no'],
			'goods_no' => $orderGoodsInfo['goods_no'],
			'goods_thumb' => $orderGoodsInfo['goods_thumb'],
			'goods_name' => $orderGoodsInfo['goods_name'],
			'chengse' => $orderGoodsInfo['chengse'],
			'zuqi_type' => $orderGoodsInfo['zuqi_type'],
			'zuqi' => $orderGoodsInfo['zuqi'],
			'begin_time' => $orderGoodsInfo['begin_time'],
			'end_time' => $orderGoodsInfo['end_time'],
			'status' => $orderGivebackInfo['status'],
			'status_name' => OrderGivebackStatus::getStatusName($orderGivebackInfo['status']),
			'instalment_num' => $orderGivebackInfo['instalment_num'],
			'instalment_amount' => $orderGivebackInfo['instalment_amount'],
			'compensate_amount' => $orderGivebackInfo['compensate_amount'],
			'payment_status' => $orderGivebackInfo['payment_status'],
			'payment_status_name' => OrderGivebackStatus::getPaymentStatusName($orderGivebackInfo['payment_status']),
			'evaluation_status' => $orderGivebackInfo['evaluation_status'],
			'evaluation_status_name' => OrderGivebackStatus::getPaymentStatusName($orderGivebackInfo['evaluation_status']),
			'payment_url' => $paymentUrl['url'],
		];
		return apiResponse($data);
	}
	
	/**
	 * 还机单支付成功的同步回调
	 * @param Request $request
	 */
	public function syncPaymentStatus( Request $request ) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :'';
		if( !isset($paramsArr['payment_status']) || $paramsArr['payment_status'] != 'success' ){
			return apiResponse([], ApiStatus::CODE_91000, '支付状态参数错误!');
		}
		if( empty($paramsArr['goods_no']) ){
			return apiResponse([], ApiStatus::CODE_91000, '商品编号不能为空!');
		}
		//创建商品服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGivebackService = new OrderGiveback();
		//开始事务
		DB::beginTransaction();
		try{
			//更新还机单状态为还机处理中
			$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], ['status'=> OrderGivebackStatus::STATUS_DEAL_IN_PAY]);
			if( !$orderGivebackResult ){
				//事务回滚
				DB::rollBack();
				return apiResponse([], ApiStatus::CODE_92700, '还机单状态更新失败!');
			}
			//同步到商品表
			$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$paramsArr['goods_no']], ['status'=> OrderGivebackStatus::STATUS_DEAL_IN_PAY]);
			if( !$orderGoodsResult ){
				//事务回滚
				DB::rollBack();
				return apiResponse([], ApiStatus::CODE_92700, '商品同步状态更新失败!');
			}
		} catch (\Exception $ex) {
			//事务回滚
			DB::rollBack();
			return apiResponse([], ApiStatus::CODE_94000, $ex->getMessage());
		}
		DB::commit();
		return apiResponse();
	}
	
	/**
	 * 检测结果处理【检测合格-代扣成功(无剩余分期)】
	 * @param OrderGiveback $orderGivebackService 还机单服务对象
	 * @param array $paramsArr 业务处理的必要参数数组
	 * $paramsArr = [<br/>
	 *		'goods_no' => '',//商品编号	【必须】<br/>
	 *		'evaluation_status' => '',//检测结果 【必须】<br/>
	 *		'evaluation_time' => '',//检测时间 【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
	 *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
	 *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
	 *		'order_no' => '',//订单编号 【必须】<br/>
	 *		'user_id' => '',//用户id 【必须】<br/>
	 *		'giveback_no' => '',//还机单编号 【必须】<br/>
	 *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
	 *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
	 *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
	 * ]
	 * @param int $status 还机单最新还机单状态
	 * @return boolen 处理结果【true:处理完成;false:处理出错】
	 */
	private function __dealEvaYesWitYes( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
		//初始化更新还机单的数据
		$data = $this->__givebackUpdateDataInit($paramsArr);
		//-+--------------------------------------------------------------------
		// | 有押金->退押金处理（执行清算处理）
		//-+--------------------------------------------------------------------
		if( $paramsArr['yajin'] ){
			//还机单清算
			$orderCleanResult = $this->__orderClean( $paramsArr );
			if( !$orderCleanResult ){
				return false;
			}
			//拼接需要更新还机单状态
			$data['status'] = $status =$goodsStatus = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
			$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
			$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_IN_RETURN;
		}
		//-+--------------------------------------------------------------------
		// | 无押金->直接修改订单
		//-+--------------------------------------------------------------------
		else{
			//解冻订单
			//查询当前订单处于还机未结束的订单数量（大于1则不能解冻订单）
			$givebackUnfinshedList = $orderGivebackService->getUnfinishedListByOrderNo($orderGivevbackInfo['order_no']);
			if( count($givebackUnfinshedList) == 1 ){
				$orderFreezeResult = \App\Order\Modules\Repository\OrderRepository::orderFreezeUpdate($orderGivevbackInfo['order_no'], \App\Order\Modules\Inc\OrderFreezeStatus::Non);
				if( !$orderFreezeResult ){
					set_apistatus(ApiStatus::CODE_92700, '订单解冻失败!');
					return false;
				}
			} 
			//拼接需要更新还机单状态
			$data['status'] = $status = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_DONE;
			$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
			$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN;
		}
		
		//更新还机单
		$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
		return $orderGivebackResult ? true : false;
	}
	
	/**
	 * 检测结果处理【检测合格-代扣失败(有剩余分期)】
	 * @param OrderGiveback $orderGivebackService 还机单服务对象
	 * @param array $paramsArr 业务处理的必要参数数组
	 * $paramsArr = [<br/>
	 *		'goods_no' => '',//商品编号	【必须】<br/>
	 *		'evaluation_status' => '',//检测结果 【必须】<br/>
	 *		'evaluation_time' => '',//检测时间 【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
	 *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
	 *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
	 *		'order_no' => '',//订单编号 【必须】<br/>
	 *		'user_id' => '',//用户id 【必须】<br/>
	 *		'giveback_no' => '',//还机单编号 【必须】<br/>
	 *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
	 *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
	 *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
	 * ]
	 * @param int $status 还机单最新还机单状态
	 * @return boolen 处理结果【true:处理完成;false:处理出错】
	 */
	private function __dealEvaYesWitNo( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
		//初始化更新还机单的数据
		$data = $this->__givebackUpdateDataInit($paramsArr);
		//-+--------------------------------------------------------------------
		// | 生成支付单，更新还机单
		//-+--------------------------------------------------------------------
		//还机单支付
		$orderPayment = $this->__orderPayment($paramsArr);
		if( !$orderPayment ){
			return false;
		}
		
		//拼接需要更新还机单状态
		$data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
		$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
		//更新还机单
		$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
		return $orderGivebackResult ? true : false;
	}
	
	/**
	 * 检测结果处理【检测不合格-代扣成功(无剩余分期)】
	 * @param OrderGiveback $orderGivebackService 还机单服务对象
	 * @param array $paramsArr 业务处理的必要参数数组
	 * $paramsArr = [<br/>
	 *		'goods_no' => '',//商品编号	【必须】<br/>
	 *		'evaluation_status' => '',//检测结果 【必须】<br/>
	 *		'evaluation_time' => '',//检测时间 【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
	 *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
	 *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
	 *		'order_no' => '',//订单编号 【必须】<br/>
	 *		'user_id' => '',//用户id 【必须】<br/>
	 *		'giveback_no' => '',//还机单编号 【必须】<br/>
	 *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
	 *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
	 *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
	 * ]
	 * @param int $status 还机单最新还机单状态
	 * @return boolen 处理结果【true:处理完成;false:处理出错】
	 */
	private function __dealEvaNoWitYes( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
		//初始化更新还机单的数据
		$data = $this->__givebackUpdateDataInit($paramsArr);
		
		//-+--------------------------------------------------------------------
		// | 业务验证（押金>=赔偿金：还机清算 || 押金<赔偿金：还机支付）、更新还机单
		//-+--------------------------------------------------------------------
		//押金>=赔偿金：还机清算
		if( $paramsArr['yajin'] >= $paramsArr['compensate_amount'] ){
			$tradeResult = $this->__orderClean($paramsArr);
			
			//拼接需要更新还机单状态更新还机单状态
			$data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
			$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
			$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_IN_RETURN;
		}
		//押金<赔偿金：还机支付
		else{
			$tradeResult = $this->__orderPayment($paramsArr);
			
			//拼接需要更新还机单状态更新还机单状态
			$data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
			$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
		}
		//清算或者支付结果失败，返回错误
		if( !$tradeResult ){
			return false;
		}
		
		//更新还机单
		$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
		return $orderGivebackResult ? true : false;
		
	}
	
	/**
	 * 检测结果处理【检测不合格-代扣失败(有剩余分期)】
	 * @param OrderGiveback $orderGivebackService 还机单服务对象
	 * @param array $paramsArr 业务处理的必要参数数组
	 * $paramsArr = [<br/>
	 *		'goods_no' => '',//商品编号	【必须】<br/>
	 *		'evaluation_status' => '',//检测结果 【必须】<br/>
	 *		'evaluation_time' => '',//检测时间 【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
	 *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
	 *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
	 *		'order_no' => '',//订单编号 【必须】<br/>
	 *		'user_id' => '',//用户id 【必须】<br/>
	 *		'giveback_no' => '',//还机单编号 【必须】<br/>
	 *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
	 *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
	 *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
	 * ]
	 * @param int $status 还机单最新还机单状态
	 * @return boolen 处理结果【true:处理完成;false:处理出错】
	 */
	private function __dealEvaNoWitNo( $paramsArr, OrderGiveback $orderGivebackService, &$status ) {
		//初始化更新还机单的数据
		$data = $this->__givebackUpdateDataInit($paramsArr);
		//-+--------------------------------------------------------------------
		// | 生成支付单，更新还机单
		//-+--------------------------------------------------------------------
		//还机单支付
		$orderPayment = $this->__orderPayment($paramsArr);
		if( !$orderPayment ){
			return false;
		}
		
		//拼接需要更新还机单状态
		$data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
		$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
		//更新还机单
		$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);
		return $orderGivebackResult ? true : false;
	}
	
	/**
	 * 订单清算处理
	 * @param array $paramsArr 业务处理的必要参数数组
	 * $paramsArr = [<br/>
	 *		'goods_no' => '',//商品编号	【必须】<br/>
	 *		'evaluation_status' => '',//检测结果 【必须】<br/>
	 *		'evaluation_time' => '',//检测时间 【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
	 *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
	 *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
	 *		'order_no' => '',//订单编号 【必须】<br/>
	 *		'user_id' => '',//用户id 【必须】<br/>
	 *		'giveback_no' => '',//还机单编号 【必须】<br/>
	 *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
	 *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
	 *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
	 * ]
	 * @return boolen 处理结果【true:处理完成;false:处理出错】
	 */
	private function __orderClean( $paramsArr ) {
		//获取当时订单支付时的相关pay的对象信息【查询payment_no和funath_no】
		$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,$paramsArr['order_no'] );
		//清算处理数据拼接
		$clearData = [
			'user_id' => $paramsArr['user_id'],
			'order_no' => $paramsArr['order_no'],
			'business_type' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			'bussiness_no' => $paramsArr['giveback_no'],
			'auth_deduction_amount' => $paramsArr['compensate_amount'],//扣除押金金额
			'auth_unfreeze_amount' => $paramsArr['yajin']-$paramsArr['compensate_amount'],//退还押金金额
			'payment_no' => $payObj->getPaymentNo(),//payment_no
			'fundauth_no' => $payObj->getFundauthNo(),//和funath_no
		];
		$orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
		if( !$orderCleanResult ){
			set_apistatus(ApiStatus::CODE_93200, '押金退还清算单创建失败!');
			return false;
		}
		return true;
	}
	
	/**
	 * 订单支付处理
	 * @param array $paramsArr 业务处理的必要参数数组
	 * $paramsArr = [<br/>
	 *		'goods_no' => '',//商品编号	【必须】<br/>
	 *		'evaluation_status' => '',//检测结果 【必须】<br/>
	 *		'evaluation_time' => '',//检测时间 【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
	 *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/><br/>
	 *		'==============' => '===============',//传入参数和查询出来参数分割线<br/><br/>
	 *		'order_no' => '',//订单编号 【必须】<br/>
	 *		'user_id' => '',//用户id 【必须】<br/>
	 *		'giveback_no' => '',//还机单编号 【必须】<br/>
	 *		'instalment_num' => '',//剩余分期期数 【必须】【可为0】<br/>
	 *		'instalment_amount' => '',//剩余分期总金额 【必须】【可为0】<br/>
	 *		'yajin' => '',//押金金额 【必须】【可为0】<br/>
	 * ]
	 * @return boolen 处理结果【true:处理完成;false:处理出错】
	 */
	private function __orderPayment( $paramsArr ) {
		try{
			//验证是否已经创建过，创建成功，返回true,未创建会抛出异常进行创建
			\App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,$paramsArr['giveback_no'] );
		} catch (\App\Lib\NotFoundException $ex) {
			$payData = [
				'businessType' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,// 业务类型 
				'businessNo' => $paramsArr['giveback_no'],// 业务编号
				'userId' => $params['user_id'],// 用户id
				'paymentAmount' => $paramsArr['instalment_amount']+$paramsArr['compensate_amount'],// Price 支付金额，单位：元
				'paymentFenqi' => 0,//不分期
			];
			\App\Order\Modules\Repository\Pay\PayCreater::createPayment($payData);
		}
		return true;
	}
	
	/**
	 * 还机单检测完成需要更新的基础数据初始化
	 * @param array $paramsArr
	 * $paramsArr = [<br/>
	 *		'evaluation_status' => '',//检测结果 【必须】<br/>
	 *		'evaluation_time' => '',//检测时间 【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注 【可选】【检测不合格时必须】<br/>
	 *		'compensate_amount' => '',//赔偿金额 【可选】【检测不合格时必须】<br/>
	 * ]
	 * @return array $data
	 * $data = [<br/>
	 *		'evaluation_status' => '',//检测结果 <br/>
	 *		'evaluation_time' => '',//检测时间 <br/>
	 *		'evaluation_remark' => '',//检测备注 <br/>
	 *		'compensate_amount' => '',//赔偿金额 【<br/>
	 * ]
	 */
	private function __givebackUpdateDataInit( $paramsArr ) {
		return [
			'evaluation_status' => $paramsArr['evaluation_status'],
			'evaluation_time' => $paramsArr['evaluation_time'],
			'evaluation_remark' => isset($paramsArr['evaluation_remark']) ? $paramsArr['evaluation_remark'] : '',
			'compensate_amount' => isset($paramsArr['compensate_amount']) ? $paramsArr['compensate_amount'] : 0,
		];
	}
}
?>
