<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Order\Models\OrderGoodExtend;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderInstalment;

class InstalmentController extends Controller
{


    // 创建订单分期
    public function create(Request $request){
        $request = $request->all();

        $order      = $request['params']['order'];
        $sku        = $request['params']['sku'];
        $coupon     = $request['params']['coupon'];
        $user       = $request['params']['user'];
        //获取goods_no
        $order = filter_array($order, [
            'order_no'=>'required',
        ]);
        if(count($order) < 1){
            return apiResponse([],ApiStatus::CODE_20001,"order_no不能为空");
        }

        //获取sku
        $sku = filter_array($sku, [
            'goods_no'=>'required',
            'zuqi'=>'required',
            'zuqi_type'=>'required',
            'all_amount'=>'required',
            'amount'=>'required',
            'yiwaixian'=>'required',
            'zujin'=>'required',
            'pay_type'=>'required',
        ]);
        if(count($sku) < 8){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误");
        }

        filter_array($coupon, [
            'discount_amount' => 'required',    //【必须】int；订单号
            'coupon_type' => 'required',    //【必须】int；订单号
        ]);


        $user = filter_array($user, [
            'withholding_no' => 'required',    //【必须】int；订单号
        ]);
        if(empty($user)){
            return apiResponse([],ApiStatus::CODE_20001,"用户代扣协议号不能为空");
        }

        $params = [
            'order'     => $order,
            'sku'       => $sku,
            'coupon'    => $coupon,
            'user'      => $user,
        ];

        $res = OrderInstalment::create($params);

        if(!$res){
            return apiResponse([],ApiStatus::CODE_20001,"用户代扣协议号不能为空");
        }

        return apiResponse([],ApiStatus::CODE_0,"success");

    }

    //分期列表接口
    public function instalment_list(Request $request){
        $request = $request->all()['params'];

        $request = filter_array($request, [
            'goods_no'=>'required',
            'order_no'=>'required',
        ]);
        if(empty($request['order_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"order_no参数错误");
        }


        $code = new OrderInstalment();
        $list = $code->queryList($request);

        if(!is_array($list)){
            return apiResponse([], $list, ApiStatus::$errCodes[$list]);
        }
        return apiResponse($list,ApiStatus::CODE_0,"success");

    }

    //代扣 扣款接口
    public function createpay(Request $request){
        $request = $request->all()['params'];
        $request = filter_array($request, [
            'instalment_id'=>'required',
            'user_id'=>'required',
            'remark'=>'required',
        ]);

        $instalment_id  = $request['instalment_id'];
        $user_id        = $request['user_id'];
        $remark         = $request['remark'];

        $instalment_info = OrderInstalment::queryByInstalmentId($instalment_id);



        p($instalment_info);


    }





}
