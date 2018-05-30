<?php
/**
 * User: wansq
 * Date: 2018/5/24
 * Time: 19:52
 */

namespace App\Warehouse\Modules\Func;


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
}