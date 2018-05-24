<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGoodsUnit;

class OrderGoodsUnitRepository{
    public function __construct()
    {
    }
    public static function add($data){
        $info =OrderGoodsUnit::create($data);
        return $info->getQueueableId();
    }
}