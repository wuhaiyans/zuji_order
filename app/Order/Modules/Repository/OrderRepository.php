<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Models\order_good_extend;
use Illuminate\Support\Facades\DB;

class OrderRepository
{

    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    public function create($data,$schema){

        var_dump('创建订单...');
        var_dump($schema);
        var_dump('创建订单结束...');die;
        $time =time();
        // 创建订单
        $order_data = [
            'order_status' => OrderStatus::OrderWaitPaying,
            'order_no' => $data['order_no'],  // 编号
            'appid'=>$data['appid'],
            'create_time' => $time,
        ];
        $order_id=1;
        // 写入用户信息
        $user_data = [
            'order_no'=>$data['order_no'],
            'user_id' =>$schema['address']['user_id'],
            'mobile' =>$schema['address']['mobile'],
            'name'=>$schema['address']['name'],
            'province_id'=>$schema['address']['province_id'],
            'city_id'=>$schema['address']['city_id'],
            'area_id'=>$schema['address']['country_id'],
            'address_info'=>$schema['address']['address'],
            'certified'=>$schema['credit']['certified'],
            'cretified_platform'=>$schema['credit']['cretified_platform'],
            'credit'=>$schema['credit']['credit'],
            'realname'=>$schema['credit']['realname'],
            'cret_no'=>$schema['credit']['cret_no'],
        ];
        // 保存 商品信息
        $goods_data = [

        ];

        // 下单减少库存

        // 存储蚁盾信息
//        $yidun_data =[
//            'verify_id' => $yidun_schema['yidun']['verify_id'],
//            'verify_uri' => $yidun_schema['yidun']['verify_uri'],
//            'decision' => $yidun_schema['yidun']['decision'],
//            'score' => $yidun_schema['yidun']['score'],
//            'strategies' =>$yidun_schema['yidun']['strategies'],
//            'level' => $yidun_schema['yidun']['level'],
//            'yidun_id' => $yidun_schema['yidun']['yidun_id'],
//        ];

}

    /**
     *
     * 根据订单id查询信息
     *
     */

    public static function getInfoById($orderNo){
            if (empty($orderNo)) return false;
            $order =  Order::query()->where([
                ['order_no', '=', $orderNo],
            ])->get();
            if (!$order) return false;
            return $order->toArray();
    }


    /**
     *
     * 根据订单id查询设备列表
     *
     */

    public static function getGoodsListByOrderId($orderNo){
        if (empty($orderNo)) return false;
        $orderGoodData =  OrderGoods::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderGoodData) return false;
        return $orderGoodData->toArray();
    }


    /**
     *
     * 根据订单号查询设备号信息
     *
     */

    public static function getGoodsExtendInfo($orderNo){
        if (empty($orderNo)) return false;
        $orderGoodExtendData =  order_good_extend::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderGoodExtendData) return false;
        return $orderGoodExtendData->toArray();
    }

    /**
     *
     * 查询未完成的订单
     *
     */
    public static function unCompledOrder($userId)
    {
        if (empty($userId)) return false;
        $orderData = Order::query()->where([
            ['user_id', '=', $userId],
            ['order_status', '<=', OrderStatus::OrderInService],
        ])->get()->toArray();
        return !empty($orderData) ?? false;

    }


    /**
     * 更新订单
     */
    public static function closeOrder($orderNo, $userId=''){

        if (empty($orderNo)) {

            return false;
        }
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        if (!empty($userId)) {

            $whereArray[] = ['user_id', '=', $userId];
        }
        $order =  Order::where($whereArray)->first();
        return $order->toArray();
        if (!$order) return false;
        $order->order_status = OrderStatus::OrderClosed;
        if ($order->save()) {
            return true;
        } else {

            return false;
        }

    }

    /**
     *  获取订单列表
     *
     * @param array $param  获取订单列表参数
     */
//    public static function getOrderList($param = array())
//    {
//            if (isset($param['userId']) && !empty($param['userId'])) {
//
//                Order::
//
//            }
//
//
//    }

    /**
     * @param array $param  orderNo 订单号
     * @return array|bool
     */
    public static function getOrderInfo($param = array())
    {
        if (empty($param)) {

            return false;
        }
        if (isset($param['orderNo']) && !empty($param['orderNo']))
        {

            $orderData = DB::table('order_info')
                ->leftJoin('order_userinfo', function ($join) {
                    $join->on('order_info.order_no', '=', 'order_userinfo.order_no');
                })
                ->where('order_info.order_no', '=', $param['orderNo'])
                ->select('order_info.*','order_userinfo.*')
                ->get();
            return $orderData->toArray();
        }



    }

}