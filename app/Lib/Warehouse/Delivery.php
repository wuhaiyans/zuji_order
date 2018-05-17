<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\Curl;
use App\Lib\Order\OrderInfo;

/**
 * Class Delivery
 * 发货系统
 */
class Delivery
{


    /**
     * 订单请求 发货申请
     *
     * @param string $order_no 订单号
     */
    public static function apply($order_no)
    {
        $base_api = config('tripartite.warehouse_api_uri');

        $info = self::getOrderDetail($order_no);

        return Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.send',//模拟
            'params' => json_encode($info)
        ]);
    }

    /**
     * 订单请求 取消发货
     *
     * @param string $order_no 订单号
     */
    public static function cancel($order_no)
    {
        $base_api = config('api.warehouse_api_uri');

        return Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.cancel',//模拟
            'params' => json_encode(['order_no'=>$order_no])
        ]);
    }


    /**
     * 客户签收后操作请求 或者自动签收
     * 接收反馈
     *
     * @param string $order_no
     * @param bool $auto 是否是自动收货 当auto=true时，为系统到期自己修改为签收
     */
    public static function receive($order_no, $auto=false)
    {
        \App\Lib\Order\Delivery::receive($order_no, $auto);
    }


    /**
     * Delivery constructor.
     * 发货反馈
     * @param array $order_no 订单号
     * [
     *      '' => '', //【必须】 string
     * ]
     */
    public static function delivery($order_no)
    {
        return true;
        \App\Lib\Order\Delivery::delivery($order_no);
    }

    /**
     * 根据order_no取发货详细内容
     * 直接调用订单那边提供的方法
     *
     * @param array $order_no 订单号
     */
    public static function getOrderDetail($order_no)
    {
        $model = new OrderInfo();
        $info = $model->getOrderInfo(['order_no'=>$order_no]);

        return $info;
    }

}