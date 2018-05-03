<?php
//路由映射
return [
    //订单相关
    'api.order.create' => 'OrderController@create',

    // 订单发货修改imei号
    'api.order.orderDeliverImei' => 'OrderController@orderDeliverImei',


    //用户相关

    'api.user.show' => 'UserController@me',


];
