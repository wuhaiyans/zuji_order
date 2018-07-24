<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Models\OrderMiniCreditRent;

/**
 * 小程序获取临时订单号接口保存请求信息
 * Class OrderMiniCreditRentRepository
 * Author zhangjinhui
 * @package App\Order\Modules\Repository
 */
class OrderMiniCreditRentRepository
{
    public function __construct()
    {}

    /**
     * 添加请求信息
     * @param $data
     * @return $last_id
     */
    public static function add($data)
    {
        $info = OrderMiniCreditRent::create($data);
        return $info->getQueueableId();
    }
}