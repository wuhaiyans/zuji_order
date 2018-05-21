<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderCoupon;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;

class OrderCouponRepository
{
    private $coupon;

    public function __construct()
    {
        $this->coupon = new OrderCoupon();
    }
    public function add($data){
        return $this->coupon->insertGetId($data);
    }


}