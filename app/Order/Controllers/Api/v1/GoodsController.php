<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;

class GoodsController extends Controller
{

    /**
     * 设备日志信息【列表】
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsLog(Request $request){
        try{
            $params = $request->input('params');
			if( empty($params['goodsNo']) ){
				throw new \Exception('设备单号不能为空');
			}
			$goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::getLogInfo($params['goodsNo']);
			foreach ($goodsLog as $key => &$value) {
				$value['create_time_show'] = date('Y-m-d H:i:s',$value['create_time']);
				$value['business_name'] = \App\Order\Modules\Inc\OrderStatus::getBusinessName($value['business_key']);
				$value['role_name'] = \App\Lib\PublicInc::getRoleName($value['operator_type']);
			}
            return apiResponse($goodsLog);
        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());
        }
    }

}
