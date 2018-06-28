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
    //长租到期一个月内出现到期处理
    'month_expiry_process_days' => 30*24*3600,
    //短期多长时间出现到期处理
    'day_expiry_process_days' => 0,
    //短期发货后多长时间变成确认收货
    'short_confirm_days' => 3*24*3600,
    //长租发货后多长时间变成确认收货
    'long_confirm_days' => 7*24*3600,
];
