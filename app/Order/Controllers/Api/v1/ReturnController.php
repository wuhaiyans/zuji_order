<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\ReturnStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderReturnCreater;
use App\Order\Modules\Service\OrderCreater;
class ReturnController extends Controller
{
    protected $OrderCreate;
    protected $OrderReturnCreater;
    public function __construct(OrderCreater $OrderCreate,OrderReturnCreater $OrderReturnCreater)
    {
        $this->OrderCreate = $OrderCreate;
        $this->OrderReturnCreater = $OrderReturnCreater;
    }

    // 申请退货接口
    public function return_apply(Request $request)
    {
        $orders =$request->all();
        $params = $orders['params'];
        if(empty($params['order_no']) ) {
            return apiResponse([],ApiStatus::CODE_20001,"订单编号不能为空");
        }
        if(empty($params['goods_no']) ) {
            return apiResponse([],ApiStatus::CODE_20001,"商品编号不能为空");
        }
        if(empty($params['user_id']) ) {
            return apiResponse([],ApiStatus::CODE_20001,"用户id不能为空");
        }
        if($params['reason_id']){
            $params['reason_text'] = "";
        }
        if (empty($params['reason_id']) && empty($params['reason_text'])) {
            return apiResponse([],ApiStatus::CODE_20001,"退货原因不能为空");
        }
        if (empty($params['business_key'])) {
            return apiResponse([],ApiStatus::CODE_20001,"业务类型不能为空");
        }
        //验证是全新未拆封还是已拆封已使用
        if ($params['loss_type']!=ReturnStatus::OrderGoodsNew && $params['loss_type']!=ReturnStatus::OrderGoodsIncomplete) {
            return apiResponse([],ApiStatus::CODE_20001,"商品损耗类型不能为空");
        }

        $where['order_no'] = $params['order_no'];
        $where['goos_no'] = $params['goods_no'];
        $res = $this->OrderCreate->get_order_info($where);
        if(empty($res)){
            return apiResponse([],ApiStatus::CODE_20001,"没有找到该订单");
        }
        $retrn_info= $this->OrderReturnCreater->get_return_info($params);
       // return apiResponse([$retrn_info],ApiStatus::CODE_0,"success");
        if($retrn_info==false){
            return apiResponse([],ApiStatus::CODE_20001,"退货单已申请");
        }
        $return = $this->OrderReturnCreater->add($params);
        return apiResponse([$return],ApiStatus::CODE_0,"success");
    }


    // 退货记录列表接口
    public function returnList(){



    }

    // 退货物流单号上传接口
    public function returnDeliverNo(){



    }

    // 退货结果查看接口
    public function returnResult(){



    }



}
