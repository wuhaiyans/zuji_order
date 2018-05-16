<?php
/**
 *
 *  订单清算数据
 *   heaven
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderCleaning;
use Illuminate\Support\Facades\Log;

class OrderCleaningController extends Controller
{

    //订单结算数据列表查询
    public function list(Request $request){

        $params = $request->input('params');
        $res = OrderCleaning::getOrderCleaningList($params);



        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }


    //订单结算详情查询
    public function detail(Request $request){


        $params = $request->all();

        $rules = [
            'business_type'  => 'required',
            'business_no'  => 'required'
        ];
        $validateParams = $this->validateParams($rules,$params);


        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }


        $res = OrderCleaning::getOrderCleanInfo($params['params']);


        return apiResponse($res,ApiStatus::CODE_0,"success");

    }


    //订单清算取消操作
    public function operate(Request $request){
        $orders =$request->all();

        $params = $request->all();

        $rules = [
            'business_type'  => 'required',
            'business_no'  => 'required'
        ];
        $validateParams = $this->validateParams($rules,$params);


        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::getOrderCleanInfo($params['params']);
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }


}
