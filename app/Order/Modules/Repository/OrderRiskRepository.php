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
    public static function getRisknfoByOrderNo($orderNo)
    {
        if (empty($orderNo)) return false;
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        $order =  OrderRisk::query()->where($whereArray)->first();
        if (!$order) return false;
        $orderRisk =$order->toArray();

        $order = OrderUserCertified::query()->where($whereArray)->first();
        if (!$order) return false;
        $orderCertified =$order->toArray();

        $orderCertified['certified_platform'] =Certification::getPlatformName($orderCertified['certified_platform']);


        return array_merge($orderCertified,$orderRisk);
    }
}