<?php
/**
 *
 * 订单结算数据处理
 */
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Order\Models\OrderClearing;

class OrderClearingRepository
{


    /**
     * 结算数据录入
     * @param $param
     * @return bool|string
     */
    public function create($param){

        if (empty($param)) {
            return false;
        }
        $orderClearData = new OrderClearing();
        // 创建结算清单
        $order_data = [
            'order_no' => OrderStatus::OrderWaitPaying,
            'business_type' => $data['order_no'],  // 编号
            'business_no'=>$data['user_id'],
            'claim_name'=>$data['pay_type'],
            'claim_amount'=>$goods_amount,
            'claim_time'=>$order_amount,
            'claim_status'=>$user_info['credit']['credit'],
            'deposit_deduction_amount'=>$goods_yajin,
            'deposit_deduction_time'=>$goods_yajin,
            'deposit_deduction_status'=>$coupon_amount,
            'deposit_unfreeze_amount'=>$data['appid'],
            'deposit_unfreeze_time'=>$data['appid'],
            'deposit_unfreeze_status'=>$data['appid'],
            'refund_amount'=>$data['appid'],
            'refund_time'=>$data['appid'],
            'refund_status'=>$data['appid'],
            'status'=>$data['appid'],
            'date'=>$data['appid'],
            'create_time'=>$data['appid'],
            'update_time'=>$data['appid'],
        ];
        $success =$orderClearData->create($order_data);
        if(!$success){
            return false;
        }
        return true;
}

    /**
     *  保存支付交易号
     */
    public static function updateTrade($orderNo, $trade_no,$userId=''){

        if (empty($orderNo)) {
            return false;
        }
        if (empty($trade_no)) {
            return false;
        }
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        if (!empty($userId)) {

            $whereArray[] = ['user_id', '=', $userId];
        }
        $order =  Order::where($whereArray)->first();
        //return $order->toArray();
        if (!$order) return false;
        $order->trade_no = $trade_no;
        if ($order->save()) {
            return true;
        } else {
            return false;
        }

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
            ])->first();
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
        $orderGoodExtendData =  OrderGoodExtend::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderGoodExtendData) return false;
        return $orderGoodExtendData->toArray();
    }
    /**
     *
     * 查询订单是否可以支付
     *
     */
    public static function isPay($orderNo)
    {
        if (empty($orderNo)) return false;
        $orderData = Order::query()->where([
            ['order_no', '=', $orderNo],
        ])->first()->toArray();
        if(empty($orderData)){
            return false;
        }
        if($orderData['order_status']!= OrderStatus::OrderWaitPaying || $orderData['pay_time'] >0){
            return false;
        }
        if(($orderData['order_amount']+$orderData['order_yajin'])<=0){
            return false;
        }
        return $orderData;

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
    //更新订单状态
    public static function order_update($order_no){
        $data['freeze_type']='1';
        if(Order::where('order_no', '=', $order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //更新订单状态
    public static function deny_update($order_no){
        $data['freeze_type']='0';
        if(Order::where('order_no', '=', $order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }
}