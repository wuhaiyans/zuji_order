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
    'Interior_Fengkong_Url' =>env("FENGKONG_API","https://dev-fk-zuji.huishoubao.com/api"),
    'Interior_Fengkong_Request_data'=>[
        'appid'=>1,
        'version'=>'1.0',
    ],
    //内部接口回调地址
    'API_INNER_URL'=>env('API_INNER_URL','http://dev-order-zuji.huishoubao.com/api'),
    'Interior_Order_Request_data'=>[
        'appid'=>1,
        'sign_type'=>'MD5',
        'sign'=>'',
        'timestamp'=>date("Y-m-d H:i:s"),
        'version'=>'1.0',
    ],
    //收发货
    'warehouse_api_uri' => env('WAREHOUSE_API_URI', 'http://dev-order-zuji.huishoubao.com/api'),

    /*********电子合同接口***********/
    //电子合同创建
    'Contract_Create_Url' => env('CONTRACT_CREATE_URL', 'http://dev-admin-zuji.huishoubao.com/index.php?m=contract&c=api&a=create'),
    //根据订单号获取电子合同
    'Contract_Order_NO_Url' => env('CONTRACT_ORDER_NO_URL', 'http://dev-admin-zuji.huishoubao.com/index.php?m=contract&c=api&a=orderNoContract'),
    //根据商品编号获取电子合同
    'Contract_Goods_NO_Url' => env('CONTRACT_GOODS_NO_URL', 'http://dev-admin-zuji.huishoubao.com/index.php?m=contract&c=api&a=goodsNoContract'),
];