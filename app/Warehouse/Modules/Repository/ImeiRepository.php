<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Modules\Inc\Imei;

class ImeiRepository
{

    private $imei;


    public function __construct(Imei $imei)
    {
        $this->imei = $imei;
    }

    /**
     * 导入imei数据
     */
    public static function import()
    {

    }


}