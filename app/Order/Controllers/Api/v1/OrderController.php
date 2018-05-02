<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $OrderCreate;

    public function __construct(Service\OrderCreater $OrderCreate)
    {
        $this->OrderCreate = $OrderCreate;
    }
    public function create(Request $request){
        $orders =$request->all();
        //获取appid
        $appid =$orders['appid'];
        $pay_type =$orders['params']['pay_type'];//支付方式ID
        $address_id=$orders['params']['address_id'];//收货地址ID
        $sku_id =$orders['params']['sku_id'];
        $coupon_no = $orders['params']['coupon_no'];

        //判断参数是否设置
        if(empty($pay_type)){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式不能为空");
        }
        if(empty($address_id)){
            return apiResponse([],ApiStatus::CODE_20001,"收货地址不能为空");
        }
        if(empty($sku_id)){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }

        $data =[
            'pay_type'=>$pay_type,
            'address_id'=>$address_id,
            'sku_id'=>$sku_id,
            'coupon_no'=>$coupon_no,
        ];
        $res = $this->OrderCreate->create($data);
    }

    public function orderList(){
        echo "订单列表接口";
    }

    public function orderDetail(Request $request){
        return apiResponse([],ApiStatus::CODE_0,"请求成功啦啦啦");
        echo "订单详情接口";

    }
}
