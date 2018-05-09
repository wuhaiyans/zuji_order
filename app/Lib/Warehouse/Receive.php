<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\Curl;

/**
 * Class Delivery
 * 收货模块
 */
class Receive
{

    /**
     * 客户点退货
     * 收货申请
     */
    public static function apply($order_no, $logistic_id, $logistic_no)
    {
        return true;
//        $base_api = config('api.warehouse_api_uri');
//
//        $response = Curl::post($base_api, [
//            'appid'=> 1,
//            'version' => 1.0,
//            'method'=> 'warehouse.delivery.send',//模拟
//            'data' => json_encode(['order_no'=>$order_no])
//        ]);
//
//        return $response;
    }

}