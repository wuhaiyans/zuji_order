<?php

//路由映射
return [
    //订单相关

    //线上下单接口
    'api.order.create' => 'OrderController@create',
    //门店下单接口
    'api.order.storeCreate' => 'OrderController@storeCreate',
    //下单确认查询接口
    'api.order.confirmation' => 'OrderController@confirmation',
    //取消订单接口
    'api.order.cancel' => 'OrderController@cancelOrder',
    //订单详情接口
    'api.order.orderdetail'=>'OrderController@orderInfo',
    //支付宝初始化接口
    'api.alipay.initialize'=>'AlipayController@alipayInitialize',
    //支付宝资金预授权接口
    'api.alipay.fundauth'=>'AlipayController@alipayFundAuth',
    //通用支付URL地址接口，满足url跳转支付
    'api.pay.payment.url'=>'PayController@getPaymentUrl',
    //银联已开通银行卡列表查询接口
    'api.union.bankCardlist'=>'UnionController@bankCardlist',
    //银联开通银行卡接口
    'api.union.openBankCard'=>'UnionController@openBankCard',
    //银联支付消费接口(限已开通银联用户)
    'api.union.consume'=>'UnionController@consume',
    //银联查询开通结果接口
    'api.union.getunionstatus'=>'UnionController@getUnionStatus',
    //银联短信验证码发送接口
    'api.union.sendsms'=>'UnionController@sendsms',

    //订单列表接口
    'api.order.orderlist'=>'OrderController@orderList',
    //订单列表筛选项接口
    'api.order.list.filter'=>'OrderController@orderListFilter',


    //订单结算清单列表接口
    'api.orderClean.list'=>'OrderCleaningController@list',
    //订单结算清单详情接口
    'api.orderClean.detail'=>'OrderCleaningController@detail',
    //订单结算清单取消接口
    'api.orderClean.cancel'=>'OrderCleaningController@cancelOrderClean',
    //更新订单清算状态
    'api.orderClean.upStatus'=>'OrderCleaningController@upOrderCleanStatus',
    //创建订单清算单
    'api.orderClean.create'=>'OrderCleaningController@createOrderClean',

    //订单清算单操作退款
    'api.orderClean.opereate'=>'OrderCleaningController@orderCleanOperate',


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
    // 代扣协议查询
    'api.Withhold.query'                => 'WithholdController@query',
    // 代扣签约接口
    'api.Withhold.sign'                 => 'WithholdController@sign',
    // 代扣签约回调接口
    'api.Withhold.sign_notify'          => 'WithholdController@sign_notify',
    // 代扣解约接口
    'api.Withhold.unsign'               => 'WithholdController@unsign',
    // 代扣解约接口回调接口
    'api.Withhold.unsign_notify'        => 'WithholdController@unsign_notify',
    // 代扣扣款接口
    'api.Withhold.repayment'            => 'WithholdController@repayment',
    // 代扣扣款接口
    'api.Withhold.createpay'            => 'WithholdController@createpay',
    // 多项扣款接口
    'api.Withhold.multi_createpay'      => 'WithholdController@multi_createpay',

    //  预授权相关
    // 资金预授权接口
    'api.Fundauth.fundauth'             => 'FundauthController@fundauth',
    // 资金预授权回调
    'api.Fundauth.fundauth_notify'      => 'FundauthController@fundauth_notify',
    // 预授权查询接口
    'api.Fundauth.fundauth_query'       => 'FundauthController@fundauthQuery',
    // 预授权解冻接口
    'api.Fundauth.fundauth_unfreeze'    => 'FundauthController@fundauth_unfreeze',
    // 预授权解冻回调接口
    'api.Fundauth.fundauth_unfreeze_notify' => 'FundauthController@fundauth_unfreeze_notify',
    // 预授权转支付接口
    'api.Fundauth.fundauth_to_pay'      => 'FundauthController@fundauth_to_pay',
    // 预授权转支付回调接口
    'api.Fundauth.fundauth_to_pay_notify'   => 'FundauthController@fundauth_to_pay_notify',


   //退货接口
    // 申请退货接口
    'api.Return.returnApply'        => 'ReturnController@returnApply',
    // 退货记录列表接口
    'api.Return.returnList'         => 'ReturnController@returnList',
    // 退货物流单号上传接口
    'api.Return.returnDeliverNo'    => 'ReturnController@returnDeliverNo',
    // 退货结果查看接口
    'api.Return.returnResult'       => 'ReturnController@returnResult',
    // 取消退货接口
    'api.Return.cancelApply'       => 'ReturnController@cancelApply',
    //申请退款
    'api.Return.returnMoney'       => 'ReturnController@returnMoney',
    //审核
    'api.Return.returnReply'       => 'ReturnController@returnReply',

    //续租接口
    // 续租页
    'api.Relet.pageRelet'       => 'ReletController@pageRelet',
    // 续租详情
    'api.Relet.detailsRelet'       => 'ReletController@detailsRelet',
    // 续租列表
    'api.Relet.listRelet'       => 'ReletController@listRelet',
    // 取消续租
    'api.Relet.cancelRelet'       => 'ReletController@cancelRelet',
	
	//-+------------------------------------------------------------------------
	// | 还机相关接口
	//-+------------------------------------------------------------------------
	//还机-设备列表信息
    'api.giveback.goods.list'       => 'GivebackController@goodsList',
	//还机申请页面接口
    'api.giveback.applying.viewdata'       => 'GivebackController@getApplyingViewdata',
	//还机申请提交接口
    'api.giveback.create'       => 'GivebackController@create',
	//还机更新状态为待检测【确认收货】
    'api.giveback.confirm.delivery'       => 'GivebackController@confirmDelivery',
	//还机更新状态【接收确认检测结果】
    'api.giveback.confirm.evaluation'       => 'GivebackController@confirmEvaluation',

    // test
    'api.Test.test'       => 'TestController@test',



    /***********************************************************************************************
     * ******************************队列消费处理接口start    heaven********************************
     ***********************************************************************************************/

    'api.inner.cancelOrder'=>'InnerServiceController@cancelOrder',//订单取消处理接口


    /*************************************************************************************************
     * ******************************队列消费处理接口end   heaven*************************************
     ************************************************************************************************/

];
