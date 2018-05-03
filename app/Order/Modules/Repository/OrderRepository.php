<?php
namespace App\Order\Modules\Repository;
use App\Order\Models\Order;
use DB;

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
     * 更新订单
     */
    public static function closeOrder($orderNo,$status){

        if (empty($orderId) or empty($userId)) {

            return false;
        }
        //更新订单状态
       $orderData =  DB::table('order')->>where([
            ['order_no', '=', $orderNo],
            ['order_status', '=', '1'],
        ])->get();

        return $orderData;


    }

}