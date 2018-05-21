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
		//-+--------------------------------------------------------------------
		// | 业务处理：冻结订单、生成还机单、推送到收发货系统【加事务】
		//-+--------------------------------------------------------------------
		//开启事务
		DB::beginTransaction();
		try{
			//生成还机单编号
			$paramsArr['giveback_no'] = $giveback_no = createNo(7);
			//初始化还机单状态
			$paramsArr['status'] = $status = OrderGivebackStatus::STATUS_DEAL_WAIT_DELIVERY;
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
				'business_key' => 1,
				'business_no' => $giveback_no,
				'goods_status' => $status,
			]);
			if(!$orderGoodsResult){
				//事务回滚
				DB::rollBack();
				return apiResponse([], get_code(), get_msg());
			}
			
			//冻结订单
			//等待接口
			

			//推送到收发货系统
			//等待接口
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
		if( $paramsArr['evaluation_status'] == OrderGivebackStatus::EVALUATION_STATUS_UNQUALIFIED && (empty($paramsArr['evaluation_remark']) || empty($paramsArr['compensate_amount'])) ){
            return apiResponse([],ApiStatus::CODE_91000,'检测不合格时：检测备注和赔偿金额均不能为空!');
		}
		$paramsArr['compensate_amount'] = isset($paramsArr['compensate_amount'])?intval($paramsArr['compensate_amount']):0;
		$goodsNo = $paramsArr['goods_no'];//商品编号提取
		
		//-+--------------------------------------------------------------------
		// | 业务处理
		//-+--------------------------------------------------------------------
		
		//-+--------------------------------------------------------------------
		// | 业务处理：判断是否需要支付【1有无未完成分期，2检测不合格的赔偿】
		//-+--------------------------------------------------------------------
		//获取商品信息
		//创建商品服务层对象
		$orderGoodsService = new OrderGoods();
		$orderGoodsInfo = $orderGoodsService->getGoodsInfo($goodsNo);
		if( !$orderGoodsInfo ) {
			return apiResponse([], get_code(), get_msg());
		}
		
		//获取当前商品是否存在分期列表
		$instalmentList = OrderInstalment::queryList(['goods_no'=>$goodsNo], ['limit'=>36,'page'=>1]);
		if( empty($instalmentList[$goodsNo]) ){
			return apiResponse([],ApiStatus::CODE_0,'数据获取成功');
		}
		//剩余分期需要支付的总金额、还机需要支付总金额
		$zujinNeedPay = $givebackNeedPay = 0;
		foreach ($instalmentList[$goodsNo] as $instalmentInfo) {
			if( in_array($instalmentInfo['status'], [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]) ){
				$zujinNeedPay += $instalmentInfo['amount'] - $instalmentInfo['discount_amount'];
			}
		}
		//存在分期单，关闭分期单
		
		//总共需要支付金额
		$givebackNeedPay = $zujinNeedPay + $paramsArr['compensate_amount'];
		//押金金额
		$yajin = $orderGoodsInfo['yajin'];
		
		//
		
		
		
	}
}
