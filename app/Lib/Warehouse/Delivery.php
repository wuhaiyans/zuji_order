<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


/**
 * Class Delivery
 * 发货系统
 */
class Delivery
{

    /**
     * 订单请求
     * 发货申请
     */
    public static function apply()
    {
        return \App\Lib\Curl::post();
    }

    /**
     * 订单请求
     * 取消发货
     */
    public static function cancel()
    {

    }


    /**
     * 客户签收后操作请求 或者自动签收
     * 接收反馈
     * 当auto=true时，为系统到期自己修改为签收
     */
    public static function receive($auto=false)
    {

    }


}