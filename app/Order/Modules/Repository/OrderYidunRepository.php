<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;

class OrderYidunRepository
{
    public function __construct()
    {
    }
    public static function add($data){
        $info =OrderYidun::create($data);
        return $info->getQueueableId();
    }
}