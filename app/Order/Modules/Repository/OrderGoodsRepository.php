<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;

class OrderGoodsRepository
{

    private $orderGoods;

    public function __construct(OrderGoods $orderGoods)
    {
        $this->orderGoods = $orderGoods;
    }
    public function create(){

        var_dump('创建商品信息');
    }
    //获取商品信息
    public static function getgoodsList($goods_no){
        if (empty($goods_no)) return false;
        $result =  orderGoods::query()->where([
            ['goods_no', '=', $goods_no],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }
}