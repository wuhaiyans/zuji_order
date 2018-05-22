<?php
/**
 * User: wansq
 * Date: 2018/5/17
 * Time: 18:03
 */


namespace App\Lib\Warehouse;
use App\Lib\Curl;

class Logistics
{
    /**
     * 根据物流id 取相关信息
     * @param int $id 物流id
     *
     */
    public static function info($id)
    {

        $base_api = config('tripartite.warehouse_api_uri');

        return Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.send',//模拟
            'params' => json_encode(['logistics_id'=>$id])
        ]);
    }

    /**
     * @param string $no 物流单号
     * 根据物流单号取物流信息
     *
     */
    public static function detail($no)
    {

    }
}