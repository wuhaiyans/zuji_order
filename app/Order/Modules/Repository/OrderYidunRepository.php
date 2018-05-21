<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;

class OrderYidunRepository
{
    private $yidun;

    public function __construct()
    {
        $this->yidun = new OrderYidun();
    }
    public function add($data){
        return $this->yidun->insertGetId($data);
    }

}