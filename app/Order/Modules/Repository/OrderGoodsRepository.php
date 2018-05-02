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

}