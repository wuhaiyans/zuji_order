<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\OrderStatus;

class OrderRepository
{

    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    public function create($data,$schema){

//        var_dump('创建订单...');
   //     var_dump($schema);
//        var_dump('创建订单结束...');die;
        $time =time();
        //用户信息
        $user_info = $schema['user_info'];
        //商品信息
        $sku_info = $schema['sku_info'];

        // 创建订单
        $order_data = [
            'order_status' => OrderStatus::OrderWaitPaying,
            'order_no' => $data['order_no'],  // 编号
            'appid'=>$data['appid'],
            'create_time' => $time,
        ];
        //var_dump($user_info);

        // 写入用户信息
        $user_data = [
            'order_no'=>$data['order_no'],
            'user_id' =>$user_info['address']['user_id'],
            'mobile' =>$user_info['address']['mobile'],
            'name'=>$user_info['address']['name'],
            'province_id'=>$user_info['address']['province_id'],
            'city_id'=>$user_info['address']['city_id'],
            'area_id'=>$user_info['address']['country_id'],
            'address_info'=>$user_info['address']['address'],
            'certified'=>$user_info['credit']['certified'],
            'cretified_platform'=>$user_info['credit']['certified_platform'],
            'credit'=>$user_info['credit']['credit'],
            'realname'=>$user_info['credit']['realname'],
            'cret_no'=>$user_info['credit']['cert_no'],
        ];
        foreach ($sku_info as $k =>$v){
            var_dump($sku_info);die;
            // 保存 商品信息
            $goods_data = [
                'goods_name'=>$v['sku']['spu_name'],
                'goods_id'=>$v['sku']['sku_id'],
                'goods_no'=>$v['sku']['sku_no'],
                'prod_id'=>$v['sku']['spu_id'],
                'prod_no'=>$v['sku']['spu_no'],
                'brand_id'=>$v['sku']['brand_id'],
                'category_id'=>$v['sku']['category_id'],
                'user_id'=>$user_info['address']['user_id'],
                'quantity'=>$v['sku']['num'],
                'goods_yajin'=>$v['sku']['yajin'],
                'yajin'=>$v['deposit']['yajin'],
                'zuqi'=>$v['sku']['zuqi'],
                'zuqi_type'=>$v['sku']['zuqi_type'],
                'zujin'=>$v['sku']['zujin'],
                'order_no'=>$data['order_no'],
                'chengse'=>$v['sku']['chengse'],
                'discount_amount'=>$v['sku']['discount_amount'],
                'amount_after_discount'=>$v['sku']['amount'],
                'edition'=>$v['sku']['edition'],
                'market_price'=>$v['sku']['market_price'],
                'price'=>"",
                'spec'=>json_encode($v['sku']['specs']),
                'insurance'=>$v['sku']['yiwaixian'],
                'buyout_price'=>$v['sku']['buyout_price'],
                'weight'=>$v['sku']['weight'],

            ];
        }

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
    public static function closeOrder($orderNo){

        if (empty($orderNo)) {

            return false;
        }
        $order =  Order::where([
            ['order_no', '=', $orderNo],
        ])->first();
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
    //获取订单信息
    public function get_order_info($where){
        $orderNo=$where['order_no'];
        $order =  Order::where([
            ['order_no', '=', $orderNo],
        ])->first();
        if (!$order){
            return false;
        }else{
            return $order;
        }
    }

}