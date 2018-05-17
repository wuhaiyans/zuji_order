<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;

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
		$data = [
			'goods_no' => '12333',
			'goods_name' => 'iPhone X 256G',
			'goods_picture' => 'https://paimgcdn.baidu.com/8C2FB68662B72E38?src=https://ss2.bdstatic.com/8_V1bjqh_Q23odCf/dsp-image/152249391.jpg&rz=urar_2_968_600&v=0',
			'status' => OrderGivebackStatus::getStatusName(OrderGivebackStatus::STATUS_APPLYING),
			'zuqi_type' => '2',
			'zuqi' => '12',
			'zuqi_begin_date' => '2018-05-16 18:30:56',
			'zuqi_end_date' => '2019-05-16 18:30:56',
			'zujin_already_pay' => '3563',
			'zujin_need_pay' => '721',
			'order_no' => '123444',
		];
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
