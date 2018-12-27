<?php
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Order\Modules\Service\OrderOverdueVisit;
use Illuminate\Http\Request;

class OverDueVisitController extends Controller
{
    /**
     * 获取回访详情
     * @author qinliping
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function  visitDetail(Request $request){
        $params             = $request->all();
        $rules = [
            'order_no'    => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        //获取最新一次的回访信息
        $visitData = OrderOverdueVisit::getOverdueVisitInfo($params['params']['order_no']);
        
        return apiResponse($visitData,ApiStatus::CODE_0,"success");
    }

    /**
     *添加 回访记录
     * @param Request $request
     */
    public function createVisit(Request $request){
        $params             = $request->all();
        $rules = [
            'order_no'    => 'required',
            'visit_id'    => 'required|int',
            'visit_text'  => 'required'
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        //创建回访记录
        $createVisit = OrderOverdueVisit::createVisit($params['params']);
        if( !$createVisit ){
            return apiResponse([],ApiStatus::CODE_95008);
        }
        return apiResponse($createVisit,ApiStatus::CODE_0);
    }







}