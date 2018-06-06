<?php
namespace App\Order\Modules\Repository;



use App\Order\Models\OrderUserCertified;

class OrderUserCertifiedRepository
{

    public static function add($data){
        $data =OrderUserCertified::create($data);
        return $data->getQueueableId();
    }

    /**
     * 根据多个订单号
     * 获取订单用户信息
     * @param string $mobile
     * @return bool
     */
    public static function getUserColumn($orderNos){
        //根据订单号
        if (!is_array($orderNos)) return false;
        array_unique($orderNos);
        $result =  OrderUserCertified::query()->wherein('order_no', $orderNos)->get()->toArray();
        if (!$result) return false;
        //指定order_no为数组下标
        return array_keys_arrange($result,"order_no");
    }
}