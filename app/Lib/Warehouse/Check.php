<?php
/**
 * User: limin
 * Date: 2019/1/21
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
/**
 * Class Delivery
 * 发货系统
 */
class Check  extends \App\Lib\BaseApi
{
    //获取检测详情
    public static function getCheckDetail($order_no,$goods_no){
        $base_api = env('WAREHOUSE_API');
        $params['order_no'] =$order_no;
        $params['goods_no'] =$goods_no;
        /*$response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.checkitem.getDetails',//模拟
            'params' => $params
        ]);
        LogApi::info("getCheckInfo 请求返回",$response);

        return $response;*/

        return self::request(\config('app.APPID'), env('WAREHOUSE_API'),'warehouse.checkitem.getDetails', $params);
    }
}