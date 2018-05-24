<?php
/**
 * User: wansq
 * Date: 2018/5/24
 * Time: 19:52
 */

namespace App\Warehouse\Modules\Func;


class WarehouseHelper
{
    /**
     * 生成单号(发货单/收货单)
     */
    public static function generateNo()
    {
        return date('YmdHis') . rand(1000, 9999);
    }
}