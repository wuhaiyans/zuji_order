<?php
//路由映射
return [
    //订单相关

    'api.order.create' => 'OrderController@create',//下单接口

    'api.order.confirmation' => 'OrderController@confirmation',//下单确认查询接口

    'api.order.cancel' => 'OrderController@cancelOrder',

    'api.order.orderdetail'=>'OrderController@orderInfo',

    'api.alipay.initialize'=>'TradeController@alipayInitialize',//支付宝初始化接口

    'api.order.orderlist'=>'OrderController@orderList',

    // 订单发货修改imei号
    'api.order.orderDeliverImei' => 'OrderController@orderDeliverImei',
    // 订单发货修改物流单号
    'api.order.updateDelivery' => 'OrderController@updateDelivery',

    // 订单发货修改物流单号
    'api.return.returnApply' => 'ReturnController@returnApply',

    //用户相关
    'api.user.show' => 'UserController@me',

    //分期相关
    // 分期列表接口
    'api.Instalment.instalment_list'    => 'InstalmentController@instalment_list',
    // 创建分期接口
    'api.Instalment.create'             => 'InstalmentController@create',
    // 扣款接口
    'api.Instalment.createpay'          => 'InstalmentController@createpay',
    // 多项扣款接口
    'api.Instalment.multi_createpay'    => 'InstalmentController@multi_createpay',

    //分期相关
    // 代扣协议查询
    'api.Withholding.query'             => 'WithholdingController@query',
    // 代扣签约接口
    'api.Withholding.sign'              => 'WithholdingController@sign',
    // 代扣解约接口
    'api.Withholding.unsign'            => 'WithholdingController@unsign',


    //退货接口
    // 申请退货接口
    'api.Return.return_apply'        => 'ReturnController@return_apply',
    // 退货记录列表接口
    'api.Return.returnList'         => 'ReturnController@returnList',
    // 退货物流单号上传接口
    'api.Return.returnDeliverNo'    => 'ReturnController@returnDeliverNo',
    // 退货结果查看接口
    'api.Return.returnResult'       => 'ReturnController@returnResult',
    // 取消退货接口
    'api.Return.cancel_apply'       => 'ReturnController@cancel_apply',
    // test
    'api.Test.test'       => 'TestController@test',

];
