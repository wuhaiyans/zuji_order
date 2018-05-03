<?php
/**
 *    订单操作类
 */
namespace App\Order\Modules\Service;
use App\Order\Modules\Repository\OrderRepository;

class OrderOperate
{

    /**
     * 取消订单
     */
    public static function cacelOrder($orderId)
    {

        if (empty($orderId)) {
            return false;
            }

       $orderData =  OrderRepository::closeOrder($orderId);
        return $orderData;

    }


}