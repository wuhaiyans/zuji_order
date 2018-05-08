<?php
namespace App\Warehouse\Modules\Reposository;
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/5/2
 * Time: 10:34
 */
class test{
    public function test(){
        // 创建订单
        $order_data = [
            'order_no' => $this->order_no,  // 编号
        ];
        $order = new Order();
        $order->order_no =$this->order_no;
        $order->save();
        $this->order_id = $order->id;
    }
}