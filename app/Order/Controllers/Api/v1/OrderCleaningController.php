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

    /**
     * 订单清算列表查询
     * @param Request $request
    params": - {
        "page":"1",                //类型：String  必有字段  备注：页码
        "status":"mock",                //类型：String    备注：出账状态
        "begin_time":1,                //类型：Number   备注：开始时间
        "end_time":1,                //类型：Number    备注：结束时间
        "app_id":1,                //类型：Number    备注：入账来源
        "out_account":"mock",                //类型：String    备注：出账方式
        "order_no":"mock"                //类型：String    备注：订单号
    }
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request){

        $params = $request->input('params');
        $res = OrderCleaning::getOrderCleaningList($params);



        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }

    /***
     * 订单结算详情查询
     * @param Request $request
     * params": - {
        "business_type":"mock",    //类型：String  必有字段  备注：业务类型
        "business_no":"mock"       //类型：String  必有字段  备注：业务编号
        }
     * @return \Illuminate\Http\JsonResponse
     */

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


    /**
     * 订单清算取消接口
     * @param Request $request
     * params": - {
        "business_type":"mock",    //类型：String  必有字段  备注：业务类型
        "business_no":"mock"       //类型：String  必有字段  备注：业务编号
            }
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrderClean(Request $request){

        $params = $request->all();

        $rules = [
            'business_type'  => 'required',
            'business_no'  => 'required'
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::cancelOrderClean($params['params']);
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }

    /**
     * 订单清算更新状态
     * params": - {
            "business_type":"mock",    //类型：String  必有字段  备注：业务类型
            "business_no":"mock"       //类型：String  必有字段  备注：业务编号
            }
     * @param Request $request
     * return json
     */

    public function upOrderCleanStatus(Request $request){

        $params = $request->all();

        $rules = [
            'business_type'  => 'required',
            'business_no'  => 'required'
        ];
        $validateParams = $this->validateParams($rules,$params);


        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::upOrderCleanStatus($params['params']);
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }

    /**
     * 创建订单清单
    params": - {
    "business_type":"mock",    //类型：String  必有字段  备注：业务类型
    "business_no":"mock"       //类型：String  必有字段  备注：业务编号
     }
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrderClean(Request $request)
    {

        $params = $request->all();

        $rules = [
            'business_type'  => 'required',
            'business_no'  => 'required'
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::createOrderClean($params['params']);
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }




    /**
     * 订单清算出帐
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderCleanOperate(Request $request)
    {

        $params = $request->all();

        $rules = [
            'business_type'  => 'required',
            'business_no'  => 'required',
            'out_refund_no'=> 'required'
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }

        $res = OrderCleaning::orderCleanOperate($params['params']);
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }





}
