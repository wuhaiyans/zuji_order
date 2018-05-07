<?php
//路由映射
return [
    //订单相关
    'api.order.create' => 'OrderController@create',

    'api.order.cancel' => 'OrderController@cancelOrder',

    // 订单发货修改imei号
    'api.order.orderDeliverImei' => 'OrderController@orderDeliverImei',
    // 订单发货修改物流单号
    'api.order.updateDelivery' => 'OrderController@updateDelivery',

    //用户相关

    'api.user.show' => 'UserController@me',

    //分期相关
    'api.Instalment.instalment_list' => 'InstalmentController@instalment_list',
    'api.Instalment.create' => 'InstalmentController@create',
];
