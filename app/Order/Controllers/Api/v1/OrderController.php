<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Models\Order;
use App\Order\Modules\Service\order_creater\OrderCreater;
use App\Order\Modules\Service\order_creater\UserComponnet;
use App\Order\Modules\Service\order_creater\SkuComponnet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function store()
    {

        echo 11;die;

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

        //开启事务
       // DB::beginTransaction();
        try{
            // 订单创建器
            $orderCreaterComponnet = new OrderCreater($order_no);
            // 用户
            $UserComponnet = new UserComponnet($orderCreaterComponnet,1);
            $orderCreaterComponnet->set_user_componnet($UserComponnet);
            // 商品
            $SkuComponnet = new SkuComponnet($orderCreaterComponnet,1,1);
            $orderCreaterComponnet->set_sku_componnet($SkuComponnet);
            var_dump($orderCreaterComponnet->create());die;

         //   DB::commit();
        } catch (\Exception $e){
      //      DB::rollback();//事务回滚
            echo $e->getMessage();
            echo $e->getCode();die;
        }

        //查询appid 是否是京东小白 如果是 调用小白接口

        //如果是其他渠道 获取支付宝信用



    }

    public function orderList(){
        echo "订单列表接口";
    }

    public function orderDetail(Request $request){
        return apiResponse([],ApiStatus::CODE_0,"请求成功啦啦啦");
        echo "订单详情接口";

    }
}
