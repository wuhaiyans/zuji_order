<?php
//路由映射
return [
    //订单相关
    'api.order.create' => 'OrderController@create',

    'api.order.cancel' => 'OrderController@cancelOrder',

    //用户相关

    'api.user.show' => 'UserController@me',


];
