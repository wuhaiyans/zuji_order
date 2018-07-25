<?php
namespace App\Order\Modules\Repository;



use App\Lib\Certification;
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
    /**
     * 根据订单号获取认证信息
     * @param $orderNo 订单编号
     * @return array
     */
    public static function getUserCertifiedByOrder($orderNo){
        if (empty($orderNo)) return false;
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        $order = OrderUserCertified::query()->where($whereArray)->first();
        if (!$order) return [];
        $orderCertified =$order->toArray();
        return $orderCertified;
    }
    /**
     * 保存用户身份证信息
     * @param $orderNo
     * @return boolean
     */
    public static function updateCardImg($orderNo,$cardImg)
    {
        if (empty($orderNo)) {
            return false;
        }
        $data['card_img'] =$cardImg;
        return OrderUserCertified::where('order_no','=',$orderNo)->update($data);

    }
}