<?php

namespace App\Order\Controllers\Api\v1;
use App\Order\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store()
    {

//        echo 2344;exit;
        $order = Order::all();
        dd($order);
//        Auth::guard('api')->fromUser($user);
//         return $this->response->array(['test_message' => 'store verification code']);

    }
    //
    public function create(Request $request){
        $orders =$request->all();
        //获取appid
        $appid =$orders['appid'];
        $pay_type =$orders['pay_type'];//支付方式ID
        $address_id=$orders['address_id'];//收货地址ID
        $sku_id =$orders['sku_id'];
        $coupon_no = $orders['coupon_no'];

        //判断参数是否设置
        if(empty($pay_type)){
            return response()->json("支付方式不能为空",400);
        }
        if(empty($address_id)){

        }
        if(empty($sku_id)){

        }

        //查询appid 是否是京东小白 如果是 调用小白接口

        //如果是其他渠道 获取支付宝信用

        //生成订单编号
        $order_no ="";



    }

    public function orderList(){

    }
}
