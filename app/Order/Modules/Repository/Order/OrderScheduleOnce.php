<?php

namespace App\Order\Modules\Repository\Order;
use App\Lib\Common\JobQueueApi;

/**
 *  订单定时任务队列
 * @author wuhaiyan
 */
class OrderScheduleOnce {

    private $userId=0;
    private $orderNo='';
    private $url='';

    /**
     * OrderScheduleOnce constructor.
     * @param $data
     * [
     *      'user_id' =>'',//用户ID
     *      'order_no'=>'',//订单编号
     * ]
     */
    public function __construct($data)
    {
        $this->orderNo = $data['order_no'];
        $this->userId  = $data['user_id'];
        $this->url     = config("ordersystem.ORDER_API");
    }

    /**
     * 风控队列
     */
    public function OrderRisk()
    {
        $this->__method([
            'method' => 'api.inner.orderRisk',
            'time' =>  time()+config('web.order_request_risk'),
            'function' => 'OrderRisk',
        ]);
    }
    /**
     * 风控押金（免押金额减少）
     */
    public function YajinReduce()
    {
        $this->__method([
            'method' => 'api.inner.yajinReduce',
            'time' => time()+config('web.order_request_risk'),
            'function' => 'YajinReduce',
        ]);
    }
    /**
     * 取消订单队列
     */
    public function CancelOrder()
    {
        $this->__method([
            'method' => 'api.inner.cancelOrder',
            'time' => time()+config('web.order_cancel_hours'),
            'function' => 'CancelOrder',
        ]);
    }
    /**
     * 小程序取消订单队列
     */
    public function miniCancelOrder()
    {
        $this->__method([
            'method' => 'api.inner.miniCancelOrder',
            'time' => time()+config('web.mini_order_cancel_hours'),
            'function' => 'CancelOrder',
        ]);
    }
    private function __method( $params ){
        //发送订单风控信息保存队列
        $b =JobQueueApi::addScheduleOnce(config('app.env').$params['function'].'_'.$this->userId,$this->url."/".$params['function'], [
            'method' => $params['method'],
            'order_no'=>$this->orderNo,
            'user_id'=>$this->userId,
            'time' => time(),
        ],$params['time'],"");
    }
}
