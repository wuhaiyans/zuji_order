<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use App\Order\Modules\Inc\OrderStatus;

class OrderRepository
{

    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    public function create(){

        var_dump('创建订单...');

    }

    /**
     *
     * 根据订单id查询信息
     *
     */

    public static function getInfoById($orderNo){
            if (empty($orderNo)) return false;
            $order =  Order::query()->where([
                ['order_no', '=', $orderNo],
            ])->get();
            if (!$order) return false;
            return $order->toArray();
    }
    /**
     * 更新订单
     */
    public static function closeOrder($orderNo){

        if (empty($orderNo)) {

            return false;
        }
        $order =  Order::where([
            ['order_no', '=', $orderNo],
        ])->first();
        if (!$order) return false;
        $order->order_status = OrderStatus::OrderClosed;

        if ($order->save()) {
            return true;
        } else {

            return false;
        }

    }

}