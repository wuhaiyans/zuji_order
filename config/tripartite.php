<?php
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/5/2
 * Time: 17:43
 */

//只有订单内部接收的接口appid =1;
return [

    'Interior_Goods_Url' =>env("GOODS_API","https://admin-zuji.huishoubao.com/api.php"),
    'Interior_Goods_Request_data'=>[
        'appid'=>1,
        'sign_type'=>'MD5',
        'sign'=>'',
        'timestamp'=>date("Y-m-d H:i:s"),
        'version'=>'1.0',
    ],
    'Interior_Fengkong_Url' =>env("API_FENGKONG_URL","https://dev-fk-zuji.huishoubao.com/api"),
    'Interior_Fengkong_Request_data'=>[
        'appid'=>1,
        'version'=>'1.0',
    ],
    //内部接口回调地址
    'API_INNER_URL'=>env('ORDER_API_URI','http://dev-order.com/api'),
    'Interior_Order_Request_data'=>[
        'appid'=>1,
        'sign_type'=>'MD5',
        'sign'=>'',
        'timestamp'=>date("Y-m-d H:i:s"),
        'version'=>'1.0',
    ],
    //收发货
    'warehouse_api_uri' => env('WAREHOUSE_API_URI', 'http://dev-order-zuji.huishoubao.com/api'),
];