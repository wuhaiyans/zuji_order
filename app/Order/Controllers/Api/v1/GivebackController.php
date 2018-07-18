<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Service\OrderGoods;
use App\Order\Modules\Service\OrderGoodsInstalment;
use App\Order\Modules\Service\OrderWithhold;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\Order\Goods;

class GivebackController extends Controller
{

	/**
	 * 公共返回方法
	 * @param Request $request
	 * @return array $data
	 */
	public static function givebackReturn( $params = [] ){
		// 拼接返回参数
		return array_merge([
			"business_type" 	=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			"business_name"		=> "还机",
			"state_flow"		=> [
				[
					'status' => OrderGivebackStatus::VIEW_STATUS_APPLYING,
					'name' => OrderGivebackStatus::getViewStatusName(OrderGivebackStatus::VIEW_STATUS_APPLYING),
				],
				[
					'status' => OrderGivebackStatus::VIEW_STATUS_CHECK,
					'name' => OrderGivebackStatus::getViewStatusName(OrderGivebackStatus::VIEW_STATUS_CHECK),
				],
				[
					'status' => OrderGivebackStatus::VIEW_STATUS_RETURN_DEPOSTI,
					'name' => OrderGivebackStatus::getViewStatusName(OrderGivebackStatus::VIEW_STATUS_RETURN_DEPOSTI),
				],
			],
		],$params);

	}
	/**
	 * 获取还机申请中页面数据
	 * @param Request $request
	 * @return type
	 */
	public function getApplyingViewdata( Request $request ) {
//		$orderGivebackService = new OrderGiveback();
//		//解冻订单
//		//查询当前订单处于还机未结束的订单数量（大于1则不能解冻订单）
//		$givebackUnfinshedList = $orderGivebackService->getUnfinishedListByOrderNo('A710116481722372');
//		var_dump($givebackUnfinshedList);exit;
		return apiResponse();return;
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
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
		$orderGoodsInfo = $this->__getOrderGoodsInfo($goodsNo);
		if( !$orderGoodsInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		//组合最终返回商品基础数据
		$data['goods_info'] = $orderGoodsInfo;//商品信息
		$data['giveback_address'] = '朝阳区朝来科技园18号院16号楼5层';//规划地址
		$data['status'] = ''.OrderGivebackStatus::adminMapView(OrderGivebackStatus::STATUS_APPLYING);//状态
		$data['status_text'] = '还机申请中';//后台状态
		
		//物流信息
		$logistics_list = [];
		$logistics = \App\Warehouse\Config::$logistics;
		foreach ($logistics as $id => $name) {
			$logistics_list[] = [
				'id' => $id,
				'name' => $name,
			];
		}
		$data['logistics_list'] = $logistics_list;//物流列表
		return apiResponse(self::givebackReturn($data),ApiStatus::CODE_0,'数据获取成功');

//		//-+--------------------------------------------------------------------
//		// | 此部分数据数据【已作废】
//		//-+--------------------------------------------------------------------
//		//默认不需要展示已支付和待支付租金价格字段
//		$data['zujin_view_flag'] = 0;
//		//判断商品租期类型【长租代扣支付需要获取分期】
//		if( $orderGoodsInfo['zuqi_type'] == 1 ){
//			return apiResponse($data,ApiStatus::CODE_0,'数据获取成功');
//		}
//		//获取当前商品是否存在分期列表
//		$instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$goodsNo], ['limit'=>36,'page'=>1]);
//		if( empty($instalmentList[$goodsNo]) ){
//			return apiResponse($data,ApiStatus::CODE_0,'数据获取成功');
//		}
//
//		//长租代扣分期，展示已支付租金和待支付租金
//		$data['zujin_view_flag'] = 1;
//		$zujinAlreadyPay = $instalmentAmount = 0;
//		foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
//			if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::PAYING, OrderInstalmentStatus::SUCCESS]) ) {
//				$zujinAlreadyPay += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
//			}
//			if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
//				$instalmentAmount += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
//			}
//		}
//		//组合最终返回价格基础数据
//		$data['zujin_already_pay'] = $zujinAlreadyPay;
//		$data['zujin_need_pay'] = $instalmentAmount;
	}
	
	private function __getOrderGoodsInfo( $goodsNo ){
		
		//获取商品基础数据
		//创建商品服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
		if( !$orderGoodsInfo ) {
			return [];
		}
		//商品信息解析
		$orderGoodsInfo['goods_specs'] = filterSpecs($orderGoodsInfo['specs']);//商品规格信息
		$orderGoodsInfo['goods_img'] = $orderGoodsInfo['goods_thumb'];//商品缩略图
		return $orderGoodsInfo;
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
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
		$paramsArr = isset($params['params'])? $params['params'] :[];
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
//				//修改商品表业务类型、商品编号、还机状态
//				$orderGoodsService = new OrderGoods();
//				$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$paramsArr['goods_no']], [
//					'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
//					'business_no' => $giveback_no,
//					'goods_status' => $status,
//				]);
				//修改商品表业务类型、商品编号、还机状态
				$orderGoods = Goods::getByGoodsNo($paramsArr['goods_no']);
				if( !$orderGoods ){
					//事务回滚
					DB::rollBack();
					return apiResponse([], ApiStatus::CODE_92401);
				}
				$orderGoodsResult = $orderGoods->givebackOpen( $giveback_no );
				if(!$orderGoodsResult){
					//事务回滚
					DB::rollBack();
					return apiResponse([],  ApiStatus::CODE_92200, '同步更新商品状态出错');
				}
				//获取用户信息
				$userInfo = \App\Order\Modules\Repository\OrderUserAddressRepository::getUserAddressInfo($paramsArr['order_no']);
				$orderGoodsInfo = $orderGoods->getData();
				//推送到收发货系统
				$warehouseResult = \App\Lib\Warehouse\Receive::create($paramsArr['order_no'], 1, [
					[
						'goods_no'=>$goodsNo,
						'goods_name'=>$orderGoodsInfo['goods_name'],
					],
				],[
					'logistics_id' => $paramsArr['logistics_id'],
					'logistics_no' => $paramsArr['logistics_no'],
					'business_key' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
					'customer' => $userInfo['name'],
					'customer_mobile' => $userInfo['consignee_mobile'],
					'customer_address' => $userInfo['address_info'],
				]);
				if( !$warehouseResult ){
					//事务回滚
					DB::rollBack();
					return apiResponse([], ApiStatus::CODE_93200, '收货单创建失败!');
				}

				//发送短信
				$notice = new \App\Order\Modules\Service\OrderNotice(
					\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
					$goodsNo,
					"GivebackCreate");
				$notice->notify();

			}
			//冻结订单

			$orderFreezeResult = \App\Order\Modules\Repository\OrderRepository::orderFreezeUpdate($paramsArr['order_no'], \App\Order\Modules\Inc\OrderFreezeStatus::Reback);
			if( !$orderFreezeResult ){
				return apiResponse([],ApiStatus::CODE_92700,'订单冻结失败！');
			}
			
			//记录日志
			$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
				'order_no'=>$paramsArr['order_no'],
				'action'=>'还机单生成',
				'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
				'business_no'=>$giveback_no,
				'goods_no'=>$goodsNo,
				'operator_id'=>$operateUserInfo['uid'],
				'operator_name'=>$operateUserInfo['username'],
				'operator_type'=>$operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
				'msg'=>'用户申请还机',
			]);
			if( !$goodsLog ){
				return apiResponse([],ApiStatus::CODE_92700,'设备日志生成失败！');
			}

		} catch (\Exception $ex) {
			//事务回滚
			DB::rollBack();
			return apiResponse([],ApiStatus::CODE_94000,$ex->getMessage());
		}
		//提交事务
		DB::commit();

//		$return  = $this->givebackReturn(['status'=>"A","status_text"=>"申请换机"]);

		return apiResponse([], ApiStatus::CODE_0, '数据获取成功');
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
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
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
		$orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($goodsNo);
		//还机单状态必须为待收货
		if( !$orderGivebackInfo ){
			return apiResponse([], get_code(), get_msg());
		}
		if( $orderGivebackInfo['status'] == OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK ){
			return apiResponse([],ApiStatus::CODE_92500,'当前还机单已经收货');
		}
		if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY ) {
			return apiResponse([],ApiStatus::CODE_92500,'当前还机单不处于待收货状态，不能进行收货操作');
		}
		//开启事务
		DB::beginTransaction();
		try{
			//-+------------------------------------------------------------------------------
			// |收货时：查询未完成分期直接进行代扣，并记录代扣状态
			//-+------------------------------------------------------------------------------
			//获取当前商品未完成分期列表数据
			$instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
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
			//记录日志
			$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
				'order_no'=>$orderGivebackInfo['order_no'],
				'action'=>'还机单收货',
				'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
				'business_no'=>$orderGivebackInfo['giveback_no'],
				'goods_no'=>$orderGivebackInfo['goods_no'],
				'operator_id'=>$operateUserInfo['uid'],
				'operator_name'=>$operateUserInfo['username'],
				'operator_type'=>$operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
				'msg'=>'还机单确认收货操作',
			]);
			if( !$goodsLog ){
				return apiResponse([],ApiStatus::CODE_92700,'设备日志生成失败！');
			}

			//发送短信
			$notice = new \App\Order\Modules\Service\OrderNotice(
				\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
				$goodsNo,
				"GivebackConfirmDelivery");
			$notice->notify();


		} catch (\Exception $ex) {
			//事务回滚
			DB::rollBack();
			return apiResponse([],ApiStatus::CODE_94000,$ex->getMessage());
		}
		//提交事务
		DB::commit();

//		$return  = $this->givebackReturn(['status'=>"B","status_text"=>"还机确认收货"]);
		return apiResponse([], ApiStatus::CODE_0, '确认收货成功');

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
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
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
		$orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($goodsNo);
		if( !$orderGivebackInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		
		if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_CHECK ){
			return apiResponse([], ApiStatus::CODE_92500, '当前还机单不处于待检测状态，不能进行检测处理!');
		}

		//获取当前商品未完成分期列表数据
		$instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]], ['limit'=>36,'page'=>1]);
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
		$paramsArr['order_no'] = $orderGivebackInfo['order_no'];//订单编号
		$paramsArr['user_id'] = $orderGivebackInfo['user_id'];//用户id
		$paramsArr['giveback_no'] = $orderGivebackInfo['giveback_no'];//还机单编号

		$paramsArr['instalment_num'] = $instalmentNum;//需要支付的分期的期数
		$paramsArr['instalment_amount'] = $instalmentAmount;//需要支付的分期的金额
		$paramsArr['yajin'] = $orderGoodsInfo['yajin'];//押金金额

		//开启事务
		DB::beginTransaction();
		try{
			//存在未完成分期单，关闭分期单
			$instalmentResult = true;
			if( $instalmentNum ){
				$instalmentResult = \App\Order\Modules\Repository\Order\Instalment::close(['goods_no'=>$goodsNo,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]]);
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


			//更新还机表状态失败回滚
			if( !$dealResult ){
				DB::rollBack();
				return apiResponse([], get_code(), get_msg());
			}
			//记录日志
			$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add([
				'order_no'=>$orderGivebackInfo['order_no'],
				'action'=>'还机单检测',
				'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,//此处用常量
				'business_no'=>$orderGivebackInfo['giveback_no'],
				'goods_no'=>$orderGivebackInfo['goods_no'],
				'operator_id'=>$operateUserInfo['uid'],
				'operator_name'=>$operateUserInfo['username'],
				'operator_type'=>$operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
				'msg'=>'还机单提交检测结果',
			]);
			if( !$goodsLog ){
				DB::rollBack();
				return apiResponse([],ApiStatus::CODE_92700,'设备日志生成失败！');
			}
		} catch (\Exception $ex) {
			//回滚事务
			DB::rollBack();
			return apiResponse([], ApiStatus::CODE_94000, $ex->getMessage());
		}
		//提交事务
		DB::commit();

//		$return  = $this->givebackReturn(['status'=>"D","status_text"=>"完成"]);
		return apiResponse([], ApiStatus::CODE_0, '检测结果同步成功');
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
		$paramsArr = isset($params['params'])? $params['params'] :[];
		$rules = [
			'goods_no'     => 'required',//还机单编号
			'callback_url'     => 'required',//回调地址
			'pay_channel_id'     => 'required',//支付的渠道id
		];
		$validator = app('validator')->make($paramsArr, $rules);
		if ($validator->fails()) {
			return apiResponse([],ApiStatus::CODE_91000,$validator->errors()->first());
		}
		//创建服务层对象
		$orderGivebackService = new OrderGiveback();
		//获取还机单基本信息
		$orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($paramsArr['goods_no']);
		if( !$orderGivebackInfo ){
			return apiResponse([], get_code(), get_msg());
		}
		$orderGoodsInfo = $this->__getOrderGoodsInfo($orderGivebackInfo['goods_no']);
		if( !$orderGoodsInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		try{
			//获取支付的url
			$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,$orderGivebackInfo['giveback_no'] );
			$paymentUrl = $payObj->getCurrentUrl($paramsArr['pay_channel_id'], [
				'name'=>'订单' .$orderGoodsInfo['order_no']. '设备'.$orderGivebackInfo['goods_no'].'还机支付',
				'front_url' => $paramsArr['callback_url'],
			]);
		} catch (\Exception $ex) {
			return apiResponse([], ApiStatus::CODE_94000,$ex->getMessage());
		}
		//拼接返回数据
		$data['goods_info'] =$orderGoodsInfo;
		$orderGivebackInfo['status_name'] = OrderGivebackStatus::getStatusName($orderGivebackInfo['status']);
		$orderGivebackInfo['payment_status_name'] = OrderGivebackStatus::getPaymentStatusName($orderGivebackInfo['payment_status']);
		$orderGivebackInfo['evaluation_status_name'] = OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['evaluation_status']);
		$data['giveback_info'] =$orderGivebackInfo;
		$data['payment_info'] =['url'=>$paymentUrl['url']];
		$data['status'] = OrderGivebackStatus::adminMapView(OrderGivebackStatus::STATUS_DEAL_WAIT_PAY);
		$data['status_text'] =OrderGivebackStatus::getStatusName(OrderGivebackStatus::STATUS_DEAL_WAIT_PAY);
		$return  = $this->givebackReturn($data);
		return apiResponse($return, ApiStatus::CODE_0, '获取支付信息');
//		$data = [
//			'order_no' => $orderGoodsInfo['order_no'],
//			'goods_no' => $orderGoodsInfo['goods_no'],
//			'goods_thumb' => $orderGoodsInfo['goods_thumb'],
//			'goods_name' => $orderGoodsInfo['goods_name'],
//			'chengse' => $orderGoodsInfo['chengse'],
//			'zuqi_type' => $orderGoodsInfo['zuqi_type'],
//			'zuqi' => $orderGoodsInfo['zuqi'],
//			'begin_time' => $orderGoodsInfo['begin_time'],
//			'end_time' => $orderGoodsInfo['end_time'],
//			'status' => $orderGivebackInfo['status'],
//			'status_name' => OrderGivebackStatus::getStatusName($orderGivebackInfo['status']),
//			'instalment_num' => $orderGivebackInfo['instalment_num'],
//			'instalment_amount' => $orderGivebackInfo['instalment_amount'],
//			'compensate_amount' => $orderGivebackInfo['compensate_amount'],
//			'payment_status' => $orderGivebackInfo['payment_status'],
//			'payment_status_name' => OrderGivebackStatus::getPaymentStatusName($orderGivebackInfo['payment_status']),
//			'evaluation_status' => $orderGivebackInfo['evaluation_status'],
//			'evaluation_status_name' => OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['evaluation_status']),
//			'payment_url' => $paymentUrl['url'],
//		];


	}

//	/**
//	 * 还机单支付成功的同步回调
//	 * @param Request $request
//	 */
//	public function syncPaymentStatus( Request $request ) {
//		//-+--------------------------------------------------------------------
//		// | 获取参数并验证
//		//-+--------------------------------------------------------------------
//		$params = $request->input();
//		$userInfo = isset($params['userinfo'])? $params['userinfo'] :[];
//		if( empty($paramsArr['uid']) || empty($paramsArr['username']) || empty($paramsArr['type']) ) {
//			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
//		}
//		$paramsArr = isset($params['params'])? $params['params'] :'';
//		if( !isset($paramsArr['payment_status']) || $paramsArr['payment_status'] != 'success' ){
//			return apiResponse([], ApiStatus::CODE_91000, '支付状态参数错误!');
//		}
//		if( empty($paramsArr['goods_no']) ){
//			return apiResponse([], ApiStatus::CODE_91000, '商品编号不能为空!');
//		}
//		//创建商品服务层对象
//		$orderGoodsService = new OrderGoods();
//		$orderGivebackService = new OrderGiveback();
//		//开始事务
//		DB::beginTransaction();
//		try{
//			//更新还机单状态为还机处理中
//			$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], ['status'=> OrderGivebackStatus::STATUS_DEAL_IN_PAY]);
//			if( !$orderGivebackResult ){
//				//事务回滚
//				DB::rollBack();
//				return apiResponse([], ApiStatus::CODE_92700, '还机单状态更新失败!');
//			}
//			//同步到商品表
//			$orderGoodsResult = $orderGoodsService->update(['goods_no'=>$paramsArr['goods_no']], ['status'=> OrderGivebackStatus::STATUS_DEAL_IN_PAY]);
//			if( !$orderGoodsResult ){
//				//事务回滚
//				DB::rollBack();
//				return apiResponse([], ApiStatus::CODE_92700, '商品同步状态更新失败!');
//			}
//		} catch (\Exception $ex) {
//			//事务回滚
//			DB::rollBack();
//			return apiResponse([], ApiStatus::CODE_94000, $ex->getMessage());
//		}
//		DB::commit();
//
//
//		return apiResponse();
//	}

	/**
	 * 获取还机搜索条件
	 * @param Request $request
	 */
	public function getStatusList(Request $request) {
		return apiResponse(['status'=>OrderGivebackStatus::getStatusList(),'kw_type'=> \App\Order\Modules\Repository\OrderGivebackRepository::getKwtypeList()]);
	}
	/**
	 * 获取还机列表
	 * @param Request $request
	 */
	public function getList(Request $request) {
		$params = $request->input();
		$whereArr = $additionArr = isset($params['params'])? $params['params'] :[];
		
		if( isset($whereArr['end_time']) ){
			$whereArr['end_time'] = date('Y-m-d 23:59:59', strtotime($whereArr['end_time']));
		}
		
		$orderGivebackService = new OrderGiveback( );
		$orderGivebackList = $orderGivebackService->getList( $whereArr, $additionArr );
		return apiResponse($orderGivebackList);

	}
    /**
     * 还机单列表导出接口
     * Author: heaven
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function listExport(Request $request) {
        $params = $request->all();
		$whereArr = $additionArr = isset($params['params'])? $params['params'] :[];
        $additionArr['page'] = 1;
        $additionArr['size'] = 10000;
		if( isset($whereArr['end_time']) && $whereArr['end_time'] ){
			$whereArr['end_time'] = date('Y-m-d 23:59:59', strtotime($whereArr['end_time']));
		}
		$orderGivebackService = new OrderGiveback( );
		$orderGivebackList = $orderGivebackService->getList( $whereArr, $additionArr );
		$total = $orderGivebackList['total'];//还机单总数

        if ($total) {

            $headers = ['订单编号','还机单生成时间','还机单单号', '用户名','手机号','设备名称','订单金额','租期','归还设备','剩余分期金额',
                '支付时间','应退押金','赔偿金','状态'];

            foreach ($orderGivebackList['data'] as $item) {
                $data[] = [
                    $item['order_no'],
                    $item['create_time'],
                    $item['giveback_no'],
                    $item['username'],
                    $item['mobile'],
                    $item['goods_name'],
                    $item['amount_after_discount'],
                    $item['zuqi'],
                    $item['goods_name'],
                    $item['instalment_amount'],
                    $item['payment_time'],
                    $item['yajin_should_return'],
                    $item['compensate_amount'],
                    $item['status_name'],
                ];
            }


            return \App\Lib\Excel::write($data, $headers,'后台还机单列表数据导出-'.$additionArr['page']);
//            return apiResponse($orderData['data'],ApiStatus::CODE_0);
        } else {
            return apiResponse([],ApiStatus::CODE_34007,'当前查询条件列表为空');
        }

    }
	
	/**
	 * 获取还机信息
	 * @param Request $request
	 */
	public function getInfo(Request $request) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :[];
		if( empty($paramsArr['goods_no']) ) {
			return apiResponse([],ApiStatus::CODE_91001);
		}
		$goodsNo = $paramsArr['goods_no'];//提取商品编号
		//-+--------------------------------------------------------------------
		// | 通过商品编号获取需要展示的数据
		//-+--------------------------------------------------------------------

		//初始化最终返回数据数组
		$data = [];
		$orderGoodsInfo = $this->__getOrderGoodsInfo($goodsNo);
		if( !$orderGoodsInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		
		//创建服务层对象
		$orderGivebackService = new OrderGiveback();
		//获取还机单基本信息
		$orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo( $goodsNo );
		//还机信息为空则返回还机申请页面信息
		if( !$orderGivebackInfo ){
			//组合最终返回商品基础数据
			$data['goods_info'] = $orderGoodsInfo;//商品信息
			$data['giveback_address'] = '朝阳区朝来科技园18号院16号楼5层';//规划地址
			$data['status'] = ''.OrderGivebackStatus::adminMapView(OrderGivebackStatus::STATUS_APPLYING);//状态
			$data['status_text'] = '还机申请中';//后台状态

			//物流信息
			$logistics_list = [];
			$logistics = \App\Warehouse\Config::$logistics;
			foreach ($logistics as $id => $name) {
				$logistics_list[] = [
					'id' => $id,
					'name' => $name,
				];
			}
			$data['logistics_list'] = $logistics_list;//物流列表
			return apiResponse(self::givebackReturn($data),ApiStatus::CODE_0,'数据获取成功');
		}
		
		
		$orderGivebackInfo['status_name'] = OrderGivebackStatus::getStatusName($orderGivebackInfo['status']);
		$orderGivebackInfo['payment_status_name'] = OrderGivebackStatus::getPaymentStatusName($orderGivebackInfo['payment_status']);
		$orderGivebackInfo['evaluation_status_name'] = OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['evaluation_status']);
		$orderGivebackInfo['yajin_status_name'] = OrderGivebackStatus::getEvaluationStatusName($orderGivebackInfo['yajin_status']);
		
		
		
		//组合最终返回商品基础数据
		$data['goods_info'] = $orderGoodsInfo;//商品信息
		$data['giveback_info'] =$orderGivebackInfo;//还机单信息
		//判断是否已经收货
		$isDelivery = false;
		if( $orderGivebackInfo['status'] != OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY ){
			$isDelivery = true;
		}
		//快递信息
		$data['logistics_info'] =[
			'logistics_name' => $orderGivebackInfo['logistics_name'],
			'logistics_no' => $orderGivebackInfo['logistics_no'],
			'is_delivery' => $isDelivery,//是否已收货
		];
		//检测结果
		if( $orderGivebackInfo['evaluation_status'] != OrderGivebackStatus::EVALUATION_STATUS_INIT ){
			$data['evaluation_info'] = [
				'evaluation_status_name' => $orderGivebackInfo['evaluation_status_name'],
				'evaluation_status_remark' => $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION? '押金已退还至支付账户，由于银行账务流水，请耐心等待1-3个工作日。':'',
				'reamrk' => '',
				'compensate_amount' => '',
			];
		}
		if( $orderGivebackInfo['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED ){
			$data['evaluation_info']['remark'] = $orderGivebackInfo['evaluation_remark'];//检测备注
			$data['evaluation_info']['compensate_amount'] = $orderGivebackInfo['compensate_amount'];//赔偿金额
		}
		//退还押金
		if( $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_IN_RETURN || $orderGivebackInfo['yajin_status'] == OrderGivebackStatus::YAJIN_STATUS_RETURN_COMOLETION ){
			$data['yajin_info'] = [
				'yajin_status_name' => $orderGivebackInfo['yajin_status_name'],
			];
		}
		
		$data['status'] = ''.OrderGivebackStatus::adminMapView($orderGivebackInfo['status']);//状态
		
		//物流信息
		return apiResponse(self::givebackReturn($data),ApiStatus::CODE_0,'数据获取成功');

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
			$data['payment_time'] = time();
			$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_IN_RETURN;
		}
		//-+--------------------------------------------------------------------
		// | 无押金->直接修改订单
		//-+--------------------------------------------------------------------
		else{
			//更新商品表状态
			$orderGoods = Goods::getByGoodsNo($paramsArr['goods_no']);
			if( !$orderGoods ){
				return false;
			}
			$orderGoodsResult = $orderGoods->givebackFinish();
			if(!$orderGoodsResult){
				return false;
			}
			//解冻订单
			if(!OrderGiveback::__unfreeze($paramsArr['order_no'])){
				set_apistatus(ApiStatus::CODE_92700, '订单解冻失败!');
				return false;
			}
			//拼接需要更新还机单状态
			$data['status'] = $status = $goodsStatus = OrderGivebackStatus::STATUS_DEAL_DONE;
			$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_NODEED_PAY;
			$data['payment_time'] = time();
			$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_NO_NEED_RETURN;
		}

		//更新还机单
		$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);

		//发送短信
		$notice = new \App\Order\Modules\Service\OrderNotice(
			\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			$paramsArr['goods_no'],
			"GivebackWithholdSuccess");
		$notice->notify();


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
		$data['payment_time'] = time();
		//更新还机单
		$orderGivebackResult = $orderGivebackService->update(['goods_no'=>$paramsArr['goods_no']], $data);

		//发送短信
		$notice = new \App\Order\Modules\Service\OrderNotice(
			\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			$paramsArr['goods_no'],
			"GivebackWithholdFail");
		$notice->notify();

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
			$data['payment_time'] = time();
			$data['yajin_status'] = OrderGivebackStatus::YAJIN_STATUS_IN_RETURN;
		}
		//押金<赔偿金：还机支付
		else{
			$tradeResult = $this->__orderPayment($paramsArr);

			//拼接需要更新还机单状态更新还机单状态
			$data['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_PAY;
			$data['payment_status'] = OrderGivebackStatus::PAYMENT_STATUS_IN_PAY;
			$data['payment_time'] = time();
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
		$data['payment_time'] = time();

		if($paramsArr['yajin'] < $paramsArr['compensate_amount']){
			$smsModel = "GivebackEvaNoWitNoEnoNo";
		}else{
			$smsModel = "GivebackEvaNoWitNoEno";
		}

		//发送短信
		$notice = new \App\Order\Modules\Service\OrderNotice(
			\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			$paramsArr['goods_no'],
			$smsModel);
		$notice->notify();

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
		if( $paramsArr['yajin'] ){
			//获取当时订单支付时的相关pay的对象信息【查询payment_no和funath_no】
			$payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,$paramsArr['order_no'] );
			$paymentNo = $payObj->getPaymentNo();
			$fundauthNo = $payObj->getFundauthNo();
		}else{
			$paymentNo = $fundauthNo = '';
		}
		//清算处理数据拼接
		$clearData = [
			'user_id' => $paramsArr['user_id'],
			'order_no' => $paramsArr['order_no'],
			'business_type' => ''.\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			'business_no' => $paramsArr['giveback_no'],
			'auth_deduction_amount' => $paramsArr['compensate_amount'],//扣除押金金额
			'auth_unfreeze_amount' => $paramsArr['yajin']-$paramsArr['compensate_amount'],//退还押金金额
			'out_payment_no' => $paymentNo,//payment_no
			'out_auth_no' => $fundauthNo,//和funath_no
		];
		$orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
		if( !$orderCleanResult ){
			set_apistatus(ApiStatus::CODE_93200, '押金退还清算单创建失败!');
			return false;
		}
		//发送短信
		$notice = new \App\Order\Modules\Service\OrderNotice(
			\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
			$goodsNo,
			"GivebackConfirmDelivery");
		$notice->notify();


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
				'orderNo' => $paramsArr['order_no'],// 订单编号
				'userId' => $paramsArr['user_id'],// 用户id
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
	 *		'instalment_amount' => '',//剩余分期金额 【可选】【存在未支付分期时必须】<br/>
	 *		'instalment_num' => '',//剩余分期数 【可选】【存在未支付分期时必须】<br/>
	 * ]
	 * @return array $data
	 * $data = [<br/>
	 *		'evaluation_status' => '',//检测结果 <br/>
	 *		'evaluation_time' => '',//检测时间 <br/>
	 *		'evaluation_remark' => '',//检测备注 <br/>
	 *		'compensate_amount' => '',//赔偿金额 【<br/>
	 *		'instalment_amount' => '',//赔偿金额 【<br/>
	 *		'instalment_num' => '',//赔偿金额 【<br/>
	 * ]
	 */
	private function __givebackUpdateDataInit( $paramsArr ) {
		return [
			'evaluation_status' => $paramsArr['evaluation_status'],
			'evaluation_time' => $paramsArr['evaluation_time'],
			'evaluation_remark' => isset($paramsArr['evaluation_remark']) ? $paramsArr['evaluation_remark'] : '',
			'compensate_amount' => isset($paramsArr['compensate_amount']) ? $paramsArr['compensate_amount'] : 0,
			'instalment_amount' => isset($paramsArr['instalment_amount']) ? $paramsArr['instalment_amount'] : 0,
			'instalment_num' => isset($paramsArr['instalment_num']) ? $paramsArr['instalment_num'] : 0,
		];
	}
}
?>
