<?php
namespace App\Order\Modules\Repository;
use App\Lib\Certification;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderRisk;
use App\Order\Models\OrderUserCertified;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;

class OrderRiskRepository
{
    public function __construct()
    {
    }

    public static function add($data){
        $info =OrderRisk::create($data);
        return $info->getQueueableId();
    }


    /**
     * 获取风控信息接口
     * Author: heaven
     * @param $orderNo
     * @return array|bool
     */
    public static function getRisknfoByOrderNo($orderNo,$type='')
    {
        if (empty($orderNo)) return false;
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        if (!empty($type)) {

            $whereArray[] = ['type', '=', $type];
        }
        $order =  DB::connection('mysql_read')->table('order_risk')->where($whereArray)->get();
        if (!$order) return false;

        return objectToArray($order);
    }
    /**
     * 根据多个订单号
     * 获取订单风控信息
     * @param string $mobile
     * @return bool
     */
    public static function getRiskColumn($orderNos){
        //根据订单号
        if (!is_array($orderNos)) return false;
        array_unique($orderNos);
        $whereArray[] = ['type', '=', 'zhima'];
        $result =  OrderRisk::query()->where($whereArray)->wherein('order_no', $orderNos)->get()->toArray();
        if (!$result) return false;
        //指定order_no为数组下标
        return array_keys_arrange($result,"order_no");
    }
}