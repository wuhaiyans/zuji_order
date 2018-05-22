<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Service\OrderGoods;
use App\Order\Modules\Service\OrderInstalment;
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
		$zujinAlreadyPay = $zujinNeedPay = 0;
		foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
			if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::PAYING, OrderInstalmentStatus::SUCCESS]) ) {
				$zujinAlreadyPay += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
			}
			if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
				$zujinNeedPay += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
			}
		}
		//组合最终返回价格基础数据
		$data['zujin_already_pay'] = $zujinAlreadyPay;
		$data['zujin_need_pay'] = $zujinNeedPay;
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
        ];
        $validator = app('validator')->make($paramsArr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
        }
		$goodsNoArr = $paramsArr['goods_no'];
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
				//等待接口
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
		$result = $orderGivebackService->update(['goods_no'=>$goodsNo], ['status'=>OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK]);
		if( !$result ){
            return apiResponse([],ApiStatus::CODE_92701);
		}
		return apiResponse([],ApiStatus::CODE_0,'确认收货成功');
	}
	
	public function confirmDetection( Request $request ) {
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
		$paramsArr['compensate_amount'] = isset($paramsArr['compensate_amount'])?intval($paramsArr['compensate_amount']):0;
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
		$instalmentList = OrderInstalment::queryList(['goods_no'=>$goodsNo], ['limit'=>36,'page'=>1]);
		//剩余分期需要支付的总金额、还机需要支付总金额
		$zujinNeedPay = $givebackNeedPay = 0;
		//剩余分期数
		$instalmentNum = 0;
		if( !empty($instalmentList[$goodsNo]) ){
			foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
				if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
					$zujinNeedPay += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
					$instalmentNum++;
				}
			}
		}
		//总共需要支付金额
		$givebackNeedPay = $zujinNeedPay + $paramsArr['compensate_amount'];
		//押金金额
		$yajin = $orderGoodsInfo['yajin'];
		
		//开启事务
		DB::beginTransaction();
		try{
			//初始化还机单需要更新的数据
			$data = [
				'instalment_num' => $instalmentNum,
				'instalment_amount' => $zujinNeedPay,
				'evaluation_status' => $paramsArr['evaluation_status'],
				'evaluation_remark' => $paramsArr['evaluation_remark'],
				'evaluation_time' => $paramsArr['evaluation_time'],
				'compensate_amount' => $paramsArr['compensate_amount'],
			];
			//存在未完成分期单，关闭分期单
			if( $instalmentNum ){
				
				//分期关闭失败，回滚
			}

			//需要支付金额和押金均为0时，直接修改还机单和商品单状态
			if( $givebackNeedPay == 0 && $yajin == 0 ) {
				//解冻订单
				//查询当前订单处于还机未结束的订单数量（大于1则不能解冻订单）
				
				$orderFreezeResult = \App\Order\Modules\Repository\OrderRepository::orderFreezeUpdate($orderGivevbackInfo['order_no'], \App\Order\Modules\Inc\OrderFreezeStatus::Non);
				//修改还机单状态
				$data['status'] = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_DONE;
				$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
				$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN;
				$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], $data);
			}
			//需要支付总金额小于等于押金金额：交由清算处理
			elseif( $givebackNeedPay <= $yajin ){
				//清算处理
				$clearData = [
					'user_id' => $orderGivevbackInfo['user_id'],
					'order_no' => $orderGivevbackInfo['order_no'],
					'business_type' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
					'bussiness_no' => $orderGivevbackInfo['giveback_no'],
					'deposit_deduction_amount' => ''.$givebackNeedPay,//扣除押金金额
					'deposit_unfreeze_amount' => ''.( $yajin - $givebackNeedPay ),//退还押金金额
				];
				$orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
				if( !$orderCleanResult ){
					//事务回滚
					DB::rollBack();
					return apiResponse([], ApiStatus::CODE_93200, '押金退还清算单创建失败!');
				}
				//更新还机单状态
				$data['status'] = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_WAIT_RETURN_DEPOSTI;
				$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
				$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_ON_RETURN;
				$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], $data);
			}
			//需要支付总金额大于押金金额：交由支付处理，在支付回调验证押金进行清算处理
			else{
				$payData = [
					'businessType' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,// 业务类型 
					'businessNo' => $orderGivevbackInfo['giveback_no'],// 业务编号
					'paymentAmount' => $givebackNeedPay,// Price 支付金额，单位：元
					'paymentFenqi' => 0,//不分期
				];
				$payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payData);
				if( !$payResult->isSuccess() ){
					//事务回滚
					DB::rollBack();
					return apiResponse([], ApiStatus::CODE_93200, '支付单创建失败!');
				}
				//更新还机单状态
				$data['status'] = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
				$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
				$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$goodsNo], $data);
			}
			
			//更新还机表状态失败回滚
			if( !$orderGivebackResult ){
				DB::rollBack();
				return apiResponse([], ApiStatus::CODE_0, '成功');
			}
			$goodsData = [
				'goods_status' => $data['status'],
			];
			//更新商品表状态
			$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$goodsNo], $goodsData);
			if( !$orderGoodsResult ){
				//事务回滚
				DB::rollBack();
				return apiResponse([], get_code(), get_msg());
			}
		} catch (Exception $ex) {
			//回滚事务
			DB::rollBack();
			return apiResponse([], ApiStatus::CODE_94000, $ex->getMessage());
		}
		//提交事务
		DB::commit();
		return apiResponse([], ApiStatus::CODE_0, '成功');
	}
	
	/**
	 * 还机单清算完成回调接口
	 * @param Request $request
	 */
	public function callbackClearing( Request $request ) {
		
	}
	
	/**
	 * 还机单支付完成回调接口
	 * @param Request $request
	 */
	public function callbackPayment( Request $request ) {
		
	}
}
