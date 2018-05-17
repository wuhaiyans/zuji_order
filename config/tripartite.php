<?php
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/5/2
 * Time: 17:43
 */

//只有订单内部接收的接口appid =1;
return [

    'Interior_Goods_Url' =>env("API_TRIPARTITE_URL","http://admin-zuji.huishoubao.com/api.php"),
    'Interior_Goods_Request_data'=>[
        'appid'=>1,
        'sign_type'=>'MD5',
        'sign'=>'',
        'timestamp'=>date("Y-m-d H:i:s"),
        'version'=>'1.0',
    ],
    'Interior_Fengkong_Url' =>env("API_FENGKONG_URL","http://admin-zuji.huishoubao.com/api.php"),
    'Interior_Fengkong_Request_data'=>[
        'appid'=>1,
        'version'=>'1.0',
    ],
    //订单内部接口地址
    'API_INNER_URL'=>"http://dev-order.com/api",
    'Interior_Order_Request_data'=>[
        'appid'=>1,
        'sign_type'=>'MD5',
        'sign'=>'',
        'timestamp'=>date("Y-m-d H:i:s"),
        'version'=>'1.0',
    ],
    //收发货
    'warehouse_api_uri' => env('WAREHOUSE_API_URI', 'http://laravel.order/api'),
];