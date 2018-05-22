<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderCoupon;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;

class OrderCouponRepository
{
    public function __construct()
    {
    }
    public static function add($data){
        $info =OrderCoupon::create($data);
        return $info->getQueueableId();
    }


}