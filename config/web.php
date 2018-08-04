<?php
/**
 *
 * author heaven
 * 全局配置信息
 */

return [

    'pre_page_size' => 20,
    //长租收货后七天内出现售后
    'month_service_days'=>7*24*3600,
    //乐百分长租收货后14天内出现售后
    'lebaifen_service_days'=>14*24*3600,
    //长租到期一个月内出现到期处理
    'month_expiry_process_days' => 30*24*3600,
    //短期多长时间出现到期处理
    'day_expiry_process_days' => 1,
    //短期发货后多长时间变成确认收货
    'short_confirm_days' => 3*24*3600,
    //长租发货后多长时间变成确认收货
    'long_confirm_days' => 7*24*3600,
    //订单多长时间未支付取消订单
    'order_cancel_hours' =>2*3600,
    //小程序订单多长时间未支付取消订单
    'mini_order_cancel_hours' =>60,
    //买断单多长时间未支付取消买断支付单
    'buyout_cancel_hours' =>60*10,
    //检查androd审核情况
    'check_verify_app_android' => env('CHECK_VERIFY_APP_ANDROID',false),
    //检查ios审核情况
    'check_verify_app_ios' => env('CHECK_VERIFY_APP_IOS',false),
    //物流配置
     'logistics' => [
            '1' => '顺丰',
    //        '2' => '中通',
    //        '3' => '圆通',
    //        '100' => '其它'
        ],
];
