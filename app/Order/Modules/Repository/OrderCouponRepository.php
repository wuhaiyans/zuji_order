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

    // 查询优惠券
    public static function find($where){
        if(!$where){
            return [];
        }

        $info = OrderCoupon::query()
            ->where($where)
            ->get();
        if(!$info){
            return [];
        }
        return $info->toArray();
    }
}