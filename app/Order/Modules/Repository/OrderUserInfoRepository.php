<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;

class OrderUserInfoRepository
{

    private $orderUserInfo;

    public function __construct(OrderUserInfo $orderUserInfo)
    {
        $this->orderUserInfo = $orderUserInfo;
    }
    public function create(){
        var_dump('创建用户信息');

    }

}