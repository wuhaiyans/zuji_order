<?php
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use Illuminate\Http\Request;

class OverDueDeductionController extends Controller
{
    /**
     * 逾期扣款列表
     * @author qinliping
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overdueDeductionList(Request $request){
        try{

            $allParams = $request->all();
            $params =   $allParams['params'];
            $overdueData = \App\Order\Modules\Service\OrderOverdueDeduction::getOverdueDeductionInfo($params);//获取逾期扣款信息

            if ($overdueData) {

                return apiResponse($overdueData['data'],ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_34007);//获取逾期扣款信息失败
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }

    }

    /**
     * 逾期扣款列表导出
     */
    public function overdueDeductionExport(){

    }

}