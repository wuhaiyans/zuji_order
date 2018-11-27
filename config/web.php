<?php
/**
 *
 * author heaven
 * 全局配置信息
 */

return [

    'pre_page_size' => 20,
    //长租收货后七天内出现售后
    'month_service_days'=>604800,
    //乐百分长租收货后14天内出现售后
    'lebaifen_service_days'=>1209600,
    //长租到期一个月内出现到期处理
    'month_expiry_process_days' => 30*24*3600,
    //短期提前1天时间出现到期处理
    'day_expiry_process_days' => 86400,
    //短期发货后多长时间变成确认收货
    'short_confirm_days' => 3*24*3600,
    //长租发货后多长时间变成确认收货
    'long_confirm_days' => 7*24*3600,
    //订单多长时间未支付取消订单
    'order_cancel_hours' =>7200,
    //小程序订单多长时间未支付取消订单
    'mini_order_cancel_hours' =>1800,
    //买断单多长时间未支付取消买断支付单
    'buyout_cancel_hours' =>600,
    //订单多长时间请求风控系统
    'order_request_risk' =>60,
    //订单预警通知邮箱
    'order_warning_user'=>'wuhaiyan@huishoubao.com.cn',
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
    //根据小程序渠道显示的订单列表
    'mini_appid' => [
        36,//支付宝
        90,//努比亚
        91,//大疆无人机
        92,//极米
        130,//长城

    ],
];
