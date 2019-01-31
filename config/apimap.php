<?php

//路由映射
return [
    
//**************************************************营销工具相关接口 **********************************************//
    //zuji.couponnew.unclaimed 暂无
    /**
     * 优惠券后台接口
     */
    //新
    'api.tool.coupon.detail'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@detail',
    //zuji.couponadmin.addtype 生成卡券类型  需对接
    'api.tool.coupon.create'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@create',
    //zuji.couponadmin.issuestart 卡卷发发布
    'api.tool.coupon.publish'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@publish',
    //zuji.couponadmin.reissue 补发 
    'api.tool.coupon.rePublish'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@rePublish',
    //zuji.couponadmin.issuestop 卡卷停止发布
    'api.tool.coupon.unPublish'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@unPublish',
    //zuji.couponadmin.delcoupon 优惠券删除
    'api.tool.coupon.remove'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@remove',
    //zuji.couponadmin.testcoupon 灰度发布
    'api.tool.coupon.greyTest'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@greyTest',
    //zuji.couponadmin.stoptestcoupon 取消灰度发布 
    'api.tool.coupon.cancelGreyTest'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@cancelGreyTest',
    //zuji.couponadmin.typelist 卡券类型列表 需对接
    'api.tool.coupon.list'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@list',
    //zuji.couponadmin.codelist 获取优惠券兑换码
    'api.tool.coupon.getCode'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@getCode',
    //导入用户
    'api.tool.coupon.importUser'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@importUser',
    //zuji.couponadmin.typelistpublic 卡券类型公共参数
    'api.tool.coupon.searchCondition'=>'\App\Tools\Controllers\Api\v1\CouponBackendController@searchCondition',
    
    
    /**
     * 优惠券客户端
     */
    //zuji.couponnew.received 获取用户已领取优惠券列表
    'api.tool.coupon.couponUserList'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponUserList',
    //zuji.couponnew.spulist 获取SPU商品详情优惠券列表
    'api.tool.coupon.spuCouponList'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@spuCouponList',
    //zuji.couponnew.checked 优惠券商品可用列表查询接口 
    'api.tool.coupon.couponListWhenOrder'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponListWhenOrder',
    //zuji.coupon.voucher.get根据用户ID获取优惠券信息(租金抵用券)
    'api.tool.coupon.couponListWhenPay'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponListWhenPay',
    //zuji.couponnew.useCode 用户使用兑换码
    'api.tool.coupon.couponUserExchange'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponUserExchange',
    //zuji.couponnew.receive 领取优惠券 用户主动领取优惠券
    'api.tool.coupon.couponUserReceive'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponUserReceive',
    //zuji.goods.coupon.status1.set 根据优惠券id设置该优惠券已使用
    'api.tool.coupon.couponUserWriteOff'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponUserWriteOff',
    //zuji.goods.coupon.status0.set 根据优惠券id恢复优惠券使用状态
    'api.tool.coupon.couponUserCancel'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponUserCancel',
    //zuji.couponnew.detail 优惠券详情
    'api.tool.coupon.couponUserDetail'=>'\App\Tools\Controllers\Api\v1\CouponFrontendController@couponUserDetail',
    
//*********************************************************************************************************************//
    
    //订单相关
    'api.order.buyout' => 'TestController@test',
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
    //订单操作日志接口
    'api.order.orderLog'=>'OrderController@orderLog',

    //订单风控审核日志接口
    'api.order.orderRiskCheckLog'=>'OrderController@orderRiskCheckLog',

    //保存订单风控审核日志
    'api.order.saveOrderRiskCheck'=>'OrderController@saveOrderRiskCheck',

    //保存回访备注信息
    'api.order.savevisit'=>'OrderController@saveOrderVisit',

    //保存订单出险信息
    'api.order.outInsurance'=>'OrderController@addOrderInsurance',
    //设备出险详情
    'api.order.outInsuranceDetail'=>'OrderController@outInsuranceDetail',

    //获取订单状态流信息
    'api.order.getOrderStatus'=>'OrderController@getOrderStatus',

    //根据订单号获取支付信息
    'api.order.getPayInfoByOrderNo'=>'OrderController@getPayInfoByOrderNo',


    
    //订单确认修改收货地址信息
    'api.order.modifyAddress'=>'OrderController@modifyAddress',

    //确认订单接口
    'api.order.confirmOrder'=>'OrderController@confirmOrder',
    //订单确认收货接口
    'api.order.deliveryReceive'=>'OrderController@deliveryReceive',
    //订单发货接口
    'api.order.delivery'=>'OrderController@delivery',
    //订单数量统计
    'api.order.counted'=>'OrderController@counted',

    //-+------------------------------------------------------------------------
	// | 设备相关
    //-+------------------------------------------------------------------------
	//设备日志列表信息
	'api.goods.log' => 'GoodsController@goodsLog',
	
	
    //gaobo
    'api.payment.url' => 'PayCenterController@pay',

    //支付宝初始化接口
    'api.alipay.initialize'=>'AlipayController@alipayInitialize',
    //支付宝资金预授权接口
    'api.alipay.fundauth'=>'AlipayController@alipayFundAuth',
    //支付宝代扣+资金预授权接口
    'api.get.payment.url'=>'PayController@payment',
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

    //客户端订单列表接口
    'api.order.orderClientlist'=>'OrderController@getClientOrderList',
    //订单列表筛选项接口
    'api.order.list.filter'=>'OrderController@orderListFilter',

    //根据订单编号查询设备列表接口
    'api.order.goodslist'=>'OrderController@getGoodsListByOrderNo',

    //订单操作日志接口
    'api.order.orderLog'=>'OrderController@orderLog',
    'api.order.getRiskInfo'=>'OrderController@getRiskInfo',


    //获取乐百分订单的分期列表
    'api.order.getLebaifenInstalment'=>'OrderController@getLebaifenInstalment',



    //订单结算清单列表接口
    'api.orderClean.list'=>'OrderCleaningController@cleanList',
    //订单结算清单详情接口
    'api.orderClean.detail'=>'OrderCleaningController@detail',
    //订单结算清单取消接口
    'api.orderClean.cancel'=>'OrderCleaningController@cancelOrderClean',
    //更新订单清算状态
    'api.orderClean.upStatus'=>'OrderCleaningController@upOrderCleanStatus',
    //创建订单清算单
    'api.orderClean.create'=>'OrderCleaningController@createOrderClean',

    //更新订单备注
    'api.orderClean.addRemark'=>'OrderCleaningController@upOrderCleanminiRecoverRemark',

    //订单清算单操作退款
    'api.orderClean.opereate'=>'OrderCleaningController@orderCleanOperate',

    //订单清算列表筛选项接口
    'api.orderClean.listFilter'=>'OrderCleaningController@orderCleaningListFilter',



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
    'api.Instalment.instalment_list'        => 'InstalmentController@instalment_list',
    // 创建分期接口
    'api.Instalment.create'                 => 'InstalmentController@create',
    // 扣款明细接口
    'api.Instalment.info'                   => 'InstalmentController@info',
    // 分期提前还款详情接口
    'api.Instalment.queryInfo'              => 'InstalmentController@queryInfo',
    // 分期提前还款详情接口
    'api.Instalment.instalmentRemark'       => 'InstalmentController@instalmentRemark',
    // 分期提前还款详情接口
    'api.Instalment.instalmentRemarkList'   => 'InstalmentController@instalmentRemarkList',
    // 分期扣款主动发送短信接口
    'api.Instalment.sendMessage'            => 'InstalmentController@sendMessage',
    //线下主动还款确认接口
    'api.Instalment.repaymentConfirm'            => 'InstalmentController@repaymentConfirm',
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
    // 提前还款
    'api.Withhold.repayment'            => 'WithholdController@repayment',
    // 代扣扣款接口
    'api.Withhold.createpay'            => 'WithholdController@createpay',
    // 多项扣款接口
    'api.Withhold.multiCreatepay'      => 'WithholdController@multiCreatepay',
    // 定时任务扣款
    'api.Withhold.crontabCreatepay'      => 'WithholdController@crontabCreatepay',

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
    'api.Return.updateDeliveryNo'    => 'ReturnController@updateDeliveryNo',
    // 退货结果查看接口
    'api.Return.returnResult'       => 'ReturnController@returnResult',
    // 取消退货接口
    'api.Return.cancelApply'       => 'ReturnController@cancelApply',
    //申请退款
    'api.Return.returnMoney'       => 'ReturnController@returnMoney',
    //退换货审核
    'api.Return.returnReply'       => 'ReturnController@returnReply',
    //订单退款审核
    'api.Return.refundReply'       => 'ReturnController@refundReply',
    //退货退款
    'api.Return.refundMoney'       => 'ReturnController@refundMoney',
    //发起退款创建清单
    'api.Return.refundTo'       => 'ReturnController@refundTo',
    //发起换货
    'api.Return.exchangeGoods'       => 'ReturnController@exchangeGoods',
    //退款成功回调
    'api.Return.refundUpdate'       => 'ReturnController@refundUpdate',
    //客户退换货已发货发起通知
    'api.Return.userReceive'       => 'ReturnController@userReceive',
    //用户换货成功回调
    'api.Return.updateOrder'       => 'ReturnController@updateOrder',
    //检测状态更改
    'api.Return.isQualified'       => 'ReturnController@isQualified',
   //获取订单所有待审核的退换货信息
    'api.Return.returnApplyList'   =>'ReturnController@returnApplyList',
    //获取订单检测不合格的数据
    'api.Return.returnCheckList'       =>'ReturnController@returnCheckList',
    //退货检测不合格的数据，拒绝退款
    'api.Return.refuseRefund'       =>'ReturnController@refuseRefund',
    //退款--取消退款
    'api.Return.cancelRefund'       =>'ReturnController@cancelRefund',
    //退款--取消退款
    'api.Return.allowReturn'       =>'ReturnController@allowReturn',
    //退换货--确认收货
    'api.Return.returnReceive'       =>'ReturnController@returnReceive',
    //线下退货退款列表
    'api.Return.underLineReturn'       =>'ReturnController@underLineReturn',






    //续租接口
    // 续租页
    'api.Relet.pageRelet'       => 'ReletController@pageRelet',
    // 续租详情
    'api.Relet.detailsRelet'       => 'ReletController@detailsRelet',
    // 续租列表(后台)
    'api.Relet.listRelet'       => 'ReletController@listRelet',
    // 取消续租
    'api.Relet.cancelRelet'       => 'ReletController@cancelRelet',
    // 获取未完成续租列表(用户)
    'api.Relet.userListRelet'       => 'ReletController@userListRelet',
    // 创建续租(支付)
    'api.Relet.createRelet'       => 'ReletController@createRelet',
    //-+------------------------------------------------------------------------
    // | 线下订单相关接口
    //-+------------------------------------------------------------------------

    //线下领取商品接口
    'api.order.activityReceive' => 'OrderController@activityReceive',

	//-+------------------------------------------------------------------------
	// | 区块链推送接口
	//-+------------------------------------------------------------------------
	'api.block.orderPushBlock' => 'OrderBlockController@orderPushBlock',
	
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
	//还机获取支付信息
    'api.giveback.get.paymentinfo'       => 'GivebackController@getPaymentInfo',
	//还机同步支付的状态
    'api.giveback.sync.paymentstatus'       => 'GivebackController@syncPaymentStatus',
	//还机获取所有状态
    'api.giveback.get.status.list'       => 'GivebackController@getStatusList',
	//还机获取列表
    'api.giveback.get.list'       => 'GivebackController@getList',
	//还机获取还机信息
    'api.giveback.get.info'       => 'GivebackController@getInfo',
	//线下门店端 待检测列表接口
	'api.giveback.UnderLineGetList'       => 'GivebackController@UnderLineGetList',
	// 线下门店端 监测不合格 获取还机支付信息
	'api.giveback.getEvaluationPayInfo'       => 'GivebackController@getEvaluationPayInfo',

    //-+------------------------------------------------------------------------
    // | 买断相关接口
    //-+------------------------------------------------------------------------
    //用户买断
    'api.buyout.userBuyout'       => 'BuyoutController@userBuyout',
    //提前买断
    'api.buyout.adminBuyout'       => 'BuyoutController@adminBuyout',
    //买断列表条件筛选
    'api.buyout.getCondition'       => 'BuyoutController@getCondition',
    //买断列表
    'api.buyout.getBuyoutList'       => 'BuyoutController@getBuyoutList',
    //买断详情
    'api.buyout.getBuyout'       => 'BuyoutController@getBuyout',
    //取消买断
    'api.buyout.cancel'       => 'BuyoutController@cancel',
    //支付宝h5买断支付
    'api.buyout.pay'       => 'BuyoutController@pay',
    
    //支付宝H5买断支付 新
    'api.payment.pay'      => 'PayCenterController@pay',
    
    //支付宝小程序h5买断支付
    'api.buyout.mini.pay'       => 'BuyoutController@mini_pay',


    //-+------------------------------------------------------------------------
    // | 收支明细
    //-+------------------------------------------------------------------------
    // 入账明细列表
    'api.pay.payIncomeQuery'        => 'PayincomeController@payIncomeQuery',
    // 入账明细详情
    'api.pay.payIncomeInfo'         => 'PayincomeController@payIncomeInfo',
    // 入账明细筛选条件
    'api.pay.payIncomeWhere'        => 'PayincomeController@payIncomeWhere',
    // 线下还款场景
    'api.pay.getOrderInfoByPhone'   => 'PayincomeController@getOrderInfoByPhone',
    // 线下缴款类型
    'api.pay.underLinePayType'      => 'PayincomeController@underLinePayType',
    // 线下还款场景
    'api.pay.underLineScene'        => 'PayincomeController@underLineScene',
    // 线下支付 获取所需要支付金额
    'api.pay.underLineGetPayAmount' => 'PayincomeController@underLineGetPayAmount',
    // 增加线下还款记录
    'api.pay.underLineAdd'          => 'PayincomeController@underLineAdd',

    // test
    'api.Test.test'       => 'TestController@test',
    'api.test.send.sms'       => 'TestController@sendSms',

    //-+------------------------------------------------------------------------
    // | 小程序相关接口
    //-+------------------------------------------------------------------------
    //小程序获取临时订单号
    'mini.order.gettemporaryorderno' => 'MiniOrderController@getTemporaryOrderNo',
    //小程序确认订单查询接口
    'mini.order.confirmation' => 'MiniOrderController@confirmationQuery',
    //小程序下单接口
    'mini.order.create' => 'MiniOrderController@create',
    //小程序订单同步接口
    'mini.order.fronttransition' => 'MiniOrderController@frontTransition',
    //取消订单接口
    'mini.order.ordercancel' => 'MiniOrderController@orderCancel',
    //小程序订单完成接口
    'mini.order.orderclose' => 'MiniOrderController@orderClose',
    //小程序获取用户最新下单订单号接口
    'mini.order.getorderno' => 'MiniOrderController@getOrderNo',
    //小程序支付后查询支付状态接口
    'mini.order.orderpaystatus' => 'MiniOrderController@orderMiniPayStatus',
//    //小程序还机信息详情接口
//    'mini.order.givebackinfo' => 'MiniGivebackController@givebackInfo',
//    //小程序订单还机提交申请接口
//    'mini.order.givebackcreate' => 'MiniGivebackController@givebackCreate',
    //小程序订单还机支付接口
    'mini.order.givebackpay' => 'MiniGivebackController@givebackPay',
    //小程序订单还机支付状态查询接口
    'mini.order.givebackpaystatus' => 'MiniGivebackController@givebackPayStatus',



    //-+------------------------------------------------------------------------
    // | 线下门店接口
    //-+------------------------------------------------------------------------

    'api.offline.orderOfflineList'        => 'OrderController@orderOfflineList',
    'api.offline.orderOffInfo'        => 'OrderController@orderOffInfo',




    /***********************************************************************************************
     * ******************************队列消费处理接口start    heaven********************************
     ***********************************************************************************************/

    'api.inner.cancelOrder'=>'InnerServiceController@cancelOrder',//订单取消处理接口
    'api.inner.miniCancelOrder'=>'InnerServiceController@miniCancelOrder',//小程序订单取消处理接口
    'api.inner.deliveryReceive'=>'InnerServiceController@deliveryReceive',//订单确认收货接口

    'api.inner.orderRisk'=>'InnerServiceController@orderRisk',//订单用户风控信息存储接口
    'api.inner.deliveryContract'=>'InnerServiceController@DeliveryContract',// 订单发货时生成合同
    'api.inner.deliveryApply'=>'InnerServiceController@DeliveryApply',// 订单申请发货


    /*************************************************************************************************
     * ******************************队列消费处理接口end   heaven*************************************
     ************************************************************************************************/

    'api.order.checkVerifyApp' => 'CheckAppController@index',

    /******************************* 运营活动短信 *************************************/

    'api.active.sendMessage' => 'ActiveController@sendMessage',

    'api.pay.paymentStatus' => 'PayController@paymentStatus',

    /*************************************************************************************************
     * ******************************异常订单处理工具*************************************
     ************************************************************************************************/

    'api.tool.Delay'          => 'ToolController@Delay',    //延期

    'api.tool.refundRefuse'  => 'ToolController@refundRefuse',   //订单状态是备货中，用户取消订单，客服审核拒绝

    'api.tool.refuseSign'    => 'ToolController@refuseSign',        //拒签

    'api.tool.advanceReturn' => 'ToolController@advanceReturn',   //超过七天无理由退换货，没到租赁日期的退货订单

    'api.tool.overDue'        => 'ToolController@overDue',          //用户逾期
    'api.tool.overDueExport' => 'ToolController@overDueExport',          //用户逾期列表导出


    'test'=>'PayController@paymentNotify',

	/*************************************************************************************************
	 * ******************************用户换绑手机号处理*************************************
	 ************************************************************************************************/
	'api.user.getInfo'        => 'UserController@getUserInfo',          //查询手机号

	'api.user.setMobile'     => 'UserController@setMobile',          //更换手机号

    /*************************************************************************************************
     * ******************************逾期扣款处理*************************************
     ************************************************************************************************/
    'api.overdueDeduction.overdueDeductionList'        => 'OverDueDeductionController@overdueDeductionList',          //逾期扣款列表
    'api.overdueDeduction.overdueDeductionExport'      => 'OverDueDeductionController@overdueDeductionExport',       //逾期扣款导出
	'api.overdueDeduction.overdueDeposit'      		   => 'OverDueDeductionController@overdueDeposit',       //逾期扣款操作
	'api.overdueDeduction.overdueDepositInfo'      	   => 'OverDueDeductionController@overdueDepositInfo',       //逾期扣款详情
	'api.overdueDeduction.overdueDepositRecord'        => 'OverDueDeductionController@overdueDepositRecord',       //逾期扣款日志
    'api.cron.test'                                         => 'CronController@test',       //定时任务逾期扣款测试
    'api.overdueVisit.visitDetail'                        => 'OverDueVisitController@visitDetail',       //逾期回访记录
    'api.overdueVisit.createVisit'                        => 'OverDueVisitController@createVisit',       //添加回访记录


];
