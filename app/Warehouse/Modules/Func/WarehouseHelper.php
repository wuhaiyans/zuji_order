<?php
/**
 * User: wansq
 * Date: 2018/5/24
 * Time: 19:52
 */

namespace App\Warehouse\Modules\Func;


use App\Lib\Curl;
use App\Warehouse\Config;

class WarehouseHelper
{
    /**
     * 生成单号(发货单/收货单)
     */
    public static function generateNo()
    {
        return date('YmdHis') . rand(1000, 9999);
    }


    public static function getLogisticsName($id)
    {
        $logis = Config::$logistics;

        return isset($logis[$id]) ? $logis[$id] : '';
    }


    /**
     * @param $id
     * @param $no
     * @return bool|void
     *
     */
    public function get($id, $no) {

        if (!$id || !$no) return false;//暂时id没用,因为只有一个顺风

        $result = Curl::post(config('url'), json_encode(['mailno'=>$no]));

        return $result;
    }
}