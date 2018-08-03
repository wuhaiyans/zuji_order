<?php
/**
 *
 * 支付阶段--签约代扣处理
 */
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderPayModel;
use App\Order\Modules\Inc\OrderStatus;

class OrderPayRepository
{

    /*
     * 查看订单的支付信息
     * @param $param $orderNo 订单编号
     * @return array
     */
    public static function find($orderNo){
        if(!$orderNo){
            return [];
        }

        $payInfo = OrderPayModel::query()
            ->where(['order_no'=>$orderNo, 'business_type'=>OrderStatus::BUSINESS_ZUJI,'business_no'=>$orderNo])
            ->first();
        if(!$payInfo){
            return [];
        }

        return $payInfo->toArray();
    }


}