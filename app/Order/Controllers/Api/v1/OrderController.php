<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\JobQueueApi;
use App\Order\Modules\Service;
use Illuminate\Http\Request;
use App\Order\Models\OrderGoodExtend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $OrderCreate;

    public function __construct(Service\OrderCreater $OrderCreate)
    {
        $this->OrderCreate = $OrderCreate;
    }

    //下单确认查询接口
    public function confirmation(Request $request){
        $orders =$request->all();
        //获取appid
        $appid =$orders['appid'];
        $pay_type =$orders['params']['pay_type'];//支付方式ID
        $sku =$orders['params']['sku_info'];
        $coupon = $orders['params']['coupon'];

        //判断参数是否设置
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"appid不能为空");
        }
        if(empty($pay_type)){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式不能为空");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }

        $data =[
            'appid'=>1,
            'pay_type'=>1,
            'sku'=>$sku,
            'coupon'=>["b997c91a2cec7918","b997c91a2cec7000"],
            'user_id'=>18,  //增加用户ID
        ];
        $res = $this->OrderCreate->confirmation($data);
        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        return apiResponse($res,ApiStatus::CODE_0,"success");

    }
    /**
     * 下单接口
     * 1.用户验证
     * 2.地址验证
     * 3.商品验证
     * 4.数据组装
     *      1：用户信息：蚁盾数据，风控数据，代扣数据
     *      2：地址信息：收货信息
     *      3：商品信息：租期类型，
     *      4：优惠券信息：
     *·
     * @param Request $request
     * $params[
     *      'pay_type'=>'',//支付方式ID
     *      'address_id'=>'',//收货地址ID
     * ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request){
        $orders =$request->all();
        //获取appid
        $appid =$orders['appid'];
        $pay_type =$orders['params']['pay_type'];//支付方式ID
        $address_id=$orders['params']['address_id'];//收货地址ID
        $sku =$orders['params']['sku_info'];
        $coupon = $orders['params']['coupon'];

        //判断参数是否设置
        if(empty($pay_type)){
            return apiResponse([],ApiStatus::CODE_20001,"支付方式不能为空");
        }
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"appid不能为空");
        }
        if(empty($address_id)){
            return apiResponse([],ApiStatus::CODE_20001,"收货地址不能为空");
        }
        if(count($sku)<1){
            return apiResponse([],ApiStatus::CODE_20001,"商品ID不能为空");
        }

        $data =[
            'appid'=>1,
            'pay_type'=>1,
            'address_id'=>$address_id,
            'sku'=>$sku,
            'coupon'=>["b997c91a2cec7918","b997c91a2cec7000"],
            'user_id'=>18,  //增加用户ID
        ];
        $res = $this->OrderCreate->creater($data);
        if(!is_array($res)){
            return apiResponse([],$res,ApiStatus::$errCodes[$res]);
        }
        //发送取消订单队列
//        $b =JobQueueApi::addScheduleOnce(env("APP_ENV")."_OrderCancel_".$res['order_no'],config("tripartite.API_INNER_URL"), [
//            'method' => 'api.inner.cancelOrder',
//            'time' => date('Y-m-d H:i:s'),
//        ],time()+7200,"");
//        Log::error($b?"Order :".$res['order_no']." IS OK":"IS error");
        return apiResponse($res,ApiStatus::CODE_0);
    }

    /**
     * 订单列表接口
     * @param Request $request
     * heaven
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderList(Request $request){
        try{

            $params = $request->input('params');

            $orderData = Service\OrderOperate::getOrderList($params);


            if ($orderData['code']===ApiStatus::CODE_0) {

                return apiResponse($orderData['data'],ApiStatus::CODE_0);
            } else {

                return apiResponse([],ApiStatus::CODE_33001);
            }

        }catch (\Exception $e) {
            return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

        }
    }

    public function orderDetail(){
        return apiResponse([],ApiStatus::CODE_0,"success");
        echo "订单详情接口";

    }

    /**
     * 未支付用户取消接口
     * @param Request $request
     * heaven
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrder(Request $request)
    {


//       $orderNo =  Service\OrderOperate::createOrderNo(1);
//       dd($orderNo);

        $params = $request->input('params');

        if (!isset($params['order_no']) || empty($params['order_no'])) {
            return apiResponse([],ApiStatus::CODE_31001,"订单号不能为空");
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
    /*
      *
      *
      * 发货后，更新物流单号
      *
      * */
    public function updateDelivery(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        if(empty($params['order_no'])){
            return ApiStatus::CODE_30005;//订单编码不能为空
        }
        if(empty($params['delivery_sn'])){
            return ApiStatus::CODE_30006;//物流单号不能为空
        }
        if(empty($params['delivery_type'])){
            return ApiStatus::CODE_30007;//物流渠道不能为空
        }
        $res = $this->OrderCreate->update($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_20001,"更新物流单号失败");
        }
        return apiResponse(['id'=>$res],ApiStatus::CODE_0,"success");

    }

    /**
     * 订单详情接口
     * heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderInfo(Request $request)
    {
            try{

                $params = $request->input('params');

                if (!isset($params['order_no']) || empty($params['order_no'])) {
                    return apiResponse([],ApiStatus::CODE_31001,"订单号不能为空");
                }

                $orderData = Service\OrderOperate::getOrderInfo($params['order_no']);
                

                if ($orderData['code']===ApiStatus::CODE_0) {

                    return apiResponse($orderData['data'],ApiStatus::CODE_0);
                } else {

                    return apiResponse([],ApiStatus::CODE_32002);
                }

            }catch (\Exception $e) {
                return apiResponse([],ApiStatus::CODE_50000,$e->getMessage());

            }


    }



}
