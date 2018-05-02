<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;

class OrderRepository
{

    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    public function create(){
        var_dump('创建订单...');

    }

}