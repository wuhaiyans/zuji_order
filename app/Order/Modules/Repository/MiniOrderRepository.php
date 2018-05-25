<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\MiniOrder;

/**
 * 小程序临时订单表
 * Class MiniOrderRepository
 * Author zhangjinhui
 * @package App\Order\Modules\Repository
 */
class MiniOrderRepository
{
    public function __construct(){}

    public static function add($data){
        $info =MiniOrder::create($data);
        return $info->getQueueableId();
    }


}