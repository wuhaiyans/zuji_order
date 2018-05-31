<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;


class AlipayController extends Controller
{
    protected $orderTrade;

    public function __construct(Service\OrderTrade $orderTrade)
    {
        $this->orderTrade = $orderTrade;
    }
    public function test(){
//        $params['business_type']=1;
//        $params['business_no']='A531153964431177'; //订单支付也就是订单编号
//        $params['status']='success';
//        $b =Service\OrderPayNotify::callback($params);
//        var_dump($b);die;
        $res =$this->alipayInitialize();
        header("Location: ".$res['url']);
    }

    /**
     * 代扣+预授权
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function withholdFundAuth(Request $request){
        $params =$request->all();
//        $params['params']=[
//            'return_url' =>'http://www.baidu.com',
//            'order_no' =>'A528100728283349',
//            'user_id' =>'18',
//        ];
        $rules = [
            'return_url'  => 'required',
            'order_no'  => 'required',
            'user_id'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
        $res= $this->orderTrade->alipayInitialize($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50004);
        }
        return apiResponse($res,ApiStatus::CODE_0);
        die;


    }

    /**
     * 支付宝初始化接口
     * $params[
     *      'return_url'=>'',//前端回调地址
     *      'order_no'=>'', //订单编号
     * ]
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function alipayInitialize(){

 //       $params =$request->all();
        $params['params']=[
            'return_url' =>'http://www.baidu.com',
            'order_no' =>'A531153474749290',
            'user_id' =>'18',
        ];
        $rules = [
            'return_url'  => 'required',
            'order_no'  => 'required',
            'user_id'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
        $res= $this->orderTrade->alipayInitialize($params);
        return $res;
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50004);
        }
        return apiResponse($res,ApiStatus::CODE_0);
        die;
    }

    /**
     * 支付宝资金预授权接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function alipayFundAuth(Request $request)
    {
        $params =$request->all();
//        $params['params']=[
//            'return_url' =>'http://www.baidu.com',
//            'order_no' =>'A528100728283349',
//            'user_id' =>'18',
//        ];
        $rules = [
            'return_url'  => 'required',
            'order_no'  => 'required',
            'user_id'=>'required',
        ];
        $validateParams = $this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code']);
        }
        $params =$params['params'];
        $res= $this->orderTrade->alipayFundAuth($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50004);
        }
        return apiResponse($res,ApiStatus::CODE_0);
        die;

    }


}
