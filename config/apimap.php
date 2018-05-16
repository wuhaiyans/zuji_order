<?php
//路由映射
return [
    //订单相关

    'api.order.create' => 'OrderController@create',//下单接口

    'api.order.confirmation' => 'OrderController@confirmation',//下单确认查询接口

    'api.order.cancel' => 'OrderController@cancelOrder', //取消订单接口

    'api.order.orderdetail'=>'OrderController@orderInfo',//订单详情接口

    'api.alipay.initialize'=>'AlipayController@alipayInitialize',//支付宝初始化接口

    'api.union.bankCardlist'=>'UnionController@bankCardlist',//银联已开通银行卡列表查询接口

    'api.union.openBankCard'=>'UnionController@openBankCard',//银联开通银行卡接口

    'api.union.consume'=>'UnionController@consume',//银联支付消费接口(限已开通银联用户)

    'api.union.getunionstatus'=>'UnionController@getUnionStatus',//银联查询开通结果接口

    'api.union.sendsms'=>'UnionController@sendsms',//银联短信验证码发送接口


    'api.order.orderlist'=>'OrderController@orderList',


    //结算清单列表接口
    'api.orderClean.list'=>'OrderCleaningController@list',
    //结算清单详情接口
    'api.orderClean.detail'=>'OrderCleaningController@detail',
    //结算清单操作接口
    'api.orderClean.operate'=>'OrderCleaningController@operate',
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

    // 代扣相关
    'api.Withhold.createpay'          => 'WithholdController@createpay',
    // 多项扣款接口
    'api.Withhold.multi_createpay'    => 'WithholdController@multi_createpay',

    // 代扣协议查询
    'api.Withholding.query'             => 'WithholdingController@query',
    // 代扣签约接口
    'api.Withholding.sign'              => 'WithholdingController@sign',
    // 代扣签约回调接口
    'api.Withholding.signNotify'        => 'WithholdingController@signNotify',
    // 代扣解约接口
    'api.Withholding.unsign'            => 'WithholdingController@unsign',
    // 代扣解约接口回调接口
    'api.Withholding.unsignNotify'      => 'WithholdingController@unsignNotify',


    //  预授权相关
    // 资金预授权接口
    'api.Fundauth.initialize'           => 'FundauthController@initialize',
    // 资金预授权回调
    'api.Fundauth.initializeNotify'     => 'WithholdingController@initializeNotify',
    // 预授权解约接口
    'api.Fundauth.unsign'               => 'WithholdingController@unsign',
    // 预授权解决接口
    'api.Fundauth.unsignNotify'         => 'WithholdingController@unsignNotify',


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
    //申请退款
    'api.Return.returnmoney'       => 'ReturnController@returnmoney',
    //换货用户收货通知
    'api.Return.updateorder'       => 'ReturnController@updateorder',
    //客户发货后通知
    'api.Return.user_receive'       => 'ReturnController@user_receive',
    //创建换货记录
    'api.Return.createchange'       => 'ReturnController@createchange',
    //创建换货记录
    'api.Return.updateStatus'       => 'ReturnController@updateStatus',
    //审核
    'api.Return.return_reply'       => 'ReturnController@return_reply',

    // test
    'api.Test.test'       => 'TestController@test',

];
