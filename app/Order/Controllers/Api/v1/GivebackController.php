<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;
use App\Order\Modules\Service\OrderGoods;
use App\Order\Modules\Service\OrderInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;

class GivebackController extends Controller
{
	/**
	 * 获取用户的风险分数
	 * @param Request $Request
	 */
	public function get_risk_score( Request $request ) {
		//获取参数并验证
		$params = $request->input();
		$params_arr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'member_id'     => 'required',
        ];
        $validator = app('validator')->make($params_arr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,"退货原因不能为空");
        }
		$system_service = new SystemService();
		$score = $system_service->get_risk_score($params_arr);
		if( $score >= RuleService::RISK_MIN_SCORE && $score <= RuleService::RISK_MAX_SCORE ) {
			return $this->respond(['score'=>$score]);
		}
		return $this->failed(ApiStatus::CODE_60001, get_error());
	}
	/**
	 * 获取还机申请中页面数据
	 * @param Request $request
	 * @return type
	 */
	public function get_applying_viewdata( Request $request ) {
		//-+--------------------------------------------------------------------
		// | 获取参数并验证
		//-+--------------------------------------------------------------------
		$params = $request->input();
		$paramsArr = isset($params['params'])? $params['params'] :'';
		if( empty($paramsArr['goods_no']) ) {
            return apiResponse([],ApiStatus::CODE_10104,'参数错误：商品编号为空!');
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
			return apiResponse([],ApiStatus::CODE_60001,'数据获取失败');
		}
		//组合最终返回商品基础数据
		$data['goods_no'] = $orderGoodsInfo['goods_no'];
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
		//获取参数并验证
		$params = $request->input();
		$params_arr = isset($params['params'])? $params['params'] :'';
        $rules = [
            'goods_no'     => 'required',//商品编号
            'order_no'     => 'required',//订单编号
            'user_id'     => 'required',//用户id
            'logistics_no'     => 'required',//物流单号
        ];
        $validator = app('validator')->make($params_arr, $rules);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_10104,$validator->errors()->first());
        }
		//-+--------------------------------------------------------------------
		// | 生成还机单、冻结订单、推送到收发货系统
		//-+--------------------------------------------------------------------
		$order_giveback_service = new OrderGiveback();
		$order_giveback_id = $order_giveback_service->create($params_arr);
		if( $order_giveback_id ){
            return apiResponse([],ApiStatus::CODE_0,'归还设备申请提交成功');
		}
		return apiResponse([],ApiStatus::CODE_10103,'归还设备申请提交失败');
	}
}
