<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:50
 */

namespace App\Lib\Order;
use App\Lib\Curl;

/**
 * Class Delivery
 * 与收发货相关
 */
class Delivery
{
    /**
     * 客户收货或系统自动签收会通知到此方法
     * @param $order_no 订单号
     * @param bool $auto $auto=true时为系统自动签收,为false时，为客户主动签收
     * 需要写成curl形式 供发货系统使用
     *
     */
    public static function receive($order_no, $auto=false)
    {

    }

    /**
     * 发货反馈到此方法
     * @param $order_no 订单号
     * @param $deliver_no 发货单号
     * 需要写成curl形式 供发货系统使用
     */
    public static function delivery($order_no, $deliver_no)
    {

    }
}