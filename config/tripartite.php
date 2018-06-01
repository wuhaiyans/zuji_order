<?php
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/5/2
 * Time: 17:43
 */

//只有订单内部接收的接口appid =1;
return [
    'Customer_Service_Phone' =>"400-080-9966",
    'Customer_Service_Address' =>"深圳市南山区高新南九道威新软件园8号楼7层",
    'Customer_Service_Name' =>"拿趣用",
    'Fengkong_Score'=>env("ORDER_SCORE",50),
    'Interior_Goods_Url' =>env("GOODS_API","https://dev-api-zuji.huishoubao.com/api.php"),
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
    'API_INNER_URL'=>env('ORDER_API_URI','http://dev-order-zuji.huishoubao.com/api'),
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