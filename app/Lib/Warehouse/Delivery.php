<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\ApiStatus;
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
     * @return boolean
     */
    public static function apply($order_no)
    {
        $base_api = config('tripartite.warehouse_api_uri');

        $info = self::getOrderDetail($order_no);

        $res= Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.send',//模拟
            'params' => json_encode($info)
        ]);
         return true;
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
     * 确认收货接口
     * 接收反馈
     *
     * @param string $order_no
     * @param int $role  在 App\Lib\publicInc 中;
     *  const Type_Admin = 1; //管理员
     *  const Type_User = 2;    //用户
     *  const Type_System = 3; // 系统自动化任务
     *  const Type_Store =4;//线下门店
     * @return boolean
     */
    public static function receive($orderNo, $role)
    {
      $response =\App\Lib\Order\Delivery::receive($orderNo, $role);
      $response = json_decode($response);
      if($response['code'] == ApiStatus::CODE_0){
          return true;
      }
       return false;

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
        $info = OrderInfo::getOrderInfo(['order_no'=>$order_no]);
        return $info;
    }

}