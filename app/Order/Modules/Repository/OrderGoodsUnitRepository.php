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

    //获取商品租期信息
    public static function getGoodsUnitInfo($goods_no){
        if (empty($goods_no)) return false;
        $result =  OrderGoodsUnit::query()->where([
            ['goods_no', '=', $goods_no],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }
}