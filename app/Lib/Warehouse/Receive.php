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

//    /**
//     * 客户点退货
//     * 收货申请
//     */
//    public static function apply($order_no, $logistic_id, $logistic_no)
//    {
//        return true;
////        $base_api = config('api.warehouse_api_uri');
////
////        $response = Curl::post($base_api, [
////            'appid'=> 1,
////            'version' => 1.0,
////            'method'=> 'warehouse.delivery.send',//模拟
////            'data' => json_encode(['order_no'=>$order_no])
////        ]);
////
////        return $response;
//    }


    /**
     * 创建待收货
     * type 类型:退 换 还 ...
     */
    public static function create($order_no,$type, $data)
    {
        $data = [
            [
                'sku_no' => 123, //goods_no
                'imei' => 'abcde',
            ],
            [
                'sku_no' => 1235, //goods_no
                'imei' => 'abcdef',
            ]
        ];


        return json_encode([
            'code' => 0,
            'msg'=> '',
            'data' => []
        ]);
    }




}