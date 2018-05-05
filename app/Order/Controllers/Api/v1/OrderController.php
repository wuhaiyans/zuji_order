<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;
use App\Order\Models\OrderGoodExtend;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $OrderCreate;

    public function __construct(Service\OrderCreater $OrderCreate)
    {
        $this->OrderCreate = $OrderCreate;
    }

    public function confirmation(Request $request){
        $orders =$request->all();
        //获取appid
        $appid =$orders['appid'];
        $pay_type =$orders['params']['pay_type'];//支付方式ID
        $sku_id =$orders['params']['sku_id'];
        $coupon_no = $orders['params']['coupon_no'];

        //判断参数是否设置
        if(empty($pay_type)){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式不能为空");
        }
        if(empty($sku_id)){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }

        $data =[
            'appid'=>$appid,
            'pay_type'=>$pay_type,
            'sku_id'=>288,
            'coupon_no'=>"b997c91a2cec7918",
            'user_id'=>18,  //增加用户ID
        ];
        $res = $this->OrderCreate->confirmation($data);
        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        return apiResponse($res,ApiStatus::CODE_0,"success");

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
            'appid'=>$appid,
            'pay_type'=>$pay_type,
            'address_id'=>$address_id,
            'sku_id'=>288,
            'coupon_no'=>"b997c91a2cec7918",
            'user_id'=>18,  //增加用户ID
        ];
        $res = $this->OrderCreate->create($data);
        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        return apiResponse($res,ApiStatus::CODE_0,"success");
    }

    public function orderList(){
        echo "订单列表接口";
    }

    public function orderDetail(){
        return apiResponse([],ApiStatus::CODE_0,"success");
        echo "订单详情接口";

    }

    /**
     *
     *  未支付用户取消接口
     *
     */
    public function cancelOrder(Request $request)
    {


//       $orderNo =  Service\OrderOperate::createOrderNo(1);
//       dd($orderNo);
        $params = $request->input('params');

        if (!isset($params['order_no']) || empty($orderNo)) {
            return apiResponse([],ApiStatus::CODE_30005,"订单号不能为空");
        }
        $code = Service\OrderOperate::cancelOrder($params['order_no']);

        return apiResponse([],ApiStatus::CODE_0,"success");


    }

    /**
     *
     *  发货后，插入设置imei号
     *
     */
    public function OrderDeliverImei(Request $request){

        $orders = $request->all();
        $params = $orders['params'];

        //判断参数是否设置
        if(empty($params['order_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"订单号不能为空");
        }
        if(empty($params['good_id'])){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }
        if(empty($params['good_no'])){
            return apiResponse([],ApiStatus::CODE_20001,"商品编码不能为空");
        }
        if(empty($params['imei1'])){
            return apiResponse([],ApiStatus::CODE_20001,"imei不能为空");
        }
        if(empty($params['serial_number'])){
            return apiResponse([],ApiStatus::CODE_20001,"序列号不能为空");
        }

        $OrderGoodExtend = new OrderGoodExtend();
        $id = $OrderGoodExtend->create($params);
        if(!$id){
            return apiResponse(['id'=>$id],ApiStatus::CODE_20001,"设置imei号失败");
        }

        return apiResponse(['id'=>$id],ApiStatus::CODE_0,"success");

    }



}
