<?php

use Illuminate\Http\Request;
/*
 |--------------------------------------------------------------------------
 | API Routes
 |--------------------------------------------------------------------------
 |
 | Here is where you can register API routes for your application. These
 | routes are loaded by the RouteServiceProvider within a group which
 | is assigned the "api" middleware group. Enjoy building your API!
 |
 */
$api = app('Dingo\Api\Routing\Router');
$api->version('v1', [
    'namespace' => 'App\Order\Controllers\Api\v1',
    'limit' => config('api.rate_limits.access.limit'),
    'expires' => config('api.rate_limits.access.expires'),
    'middleware' => 'api.throttle'
], function($api) {
    $api->post('token', 'UserController@token');    //获取token
    $api->post('refresh-token', 'UserController@refershToken'); //刷新token

    $api->post('trade/notify', 'TradeController@notify'); //支付回调接口




        $apiMap = config('apimap');

        $method = request()->input('method');
        if (isset($apiMap[$method])) {
			\App\Lib\Common\LogApi::setSource( str_replace('.', '_', $method) );
			\App\Lib\Common\LogApi::debug( $method.':params',request()->all());
            $api->post('/',  $apiMap[$method]);
        }

        $api->post('user', 'UserController@me') //获取用户信息接口
            ->name('api.user.show');


        // 订单清算测试预授权
        $api->get('testCleanPay', 'OrderCleaningController@testPay');

        // 代扣扣款回调
        $api->post('createpayNotify', 'WithholdController@createpayNotify');

        // 提前还款回调
        $api->post('repaymentNotify', 'WithholdController@repaymentNotify');

        // 代扣解约回调
        $api->post('unSignNotify', 'WithholdController@unSignNotify');


        // 预授权回调
        $api->post('fundauthNotify', 'PayController@fundauth_notify');

        // 预授权解冻回调
        $api->post('fundauthUnfreezeNotify', 'PayController@fundauth_unfreeze_notify');

        //预授权转支付回调
        $api->post('unfreezeAndPayNotify', 'PayController@unfreeze_pay_notify');

        //订单清算押金转支付回调接口
        $api->post('unfreezeAndPayClean', 'PayController@unfreezeAndPayClean');

        //订单清算退款回调接口
        $api->post('fundauthToPayNotify', 'PayController@fundauthToPayNotify');

        //逾期扣款回调接口
        $api->post('deduDepositNotify', 'PayController@deduDepositNotify');

        //还机扣款回调接口
        $api->post('givebackDeductionDepositNotify', 'PayController@givebackDeductionDepositNotify');

        //订单清算退款回调接口
        $api->post('refundClean', 'PayController@refundClean');

        //订单清算退押金回调接口
        $api->post('unFreezeClean', 'PayController@unFreezeClean');

        //订单清算微回收押金解除接口
        $api->post('lebaiUnfreezeClean', 'PayController@lebaiUnfreezeClean');

        //分期定时扣款统计数量接口
        $api->any('crontabCreatepayNum', 'WithholdController@crontabCreatepayNum');

        $api->any('orderListExport', 'OrderController@orderListExport');
		//还机列表导出
        $api->any('givebackListExport', 'GivebackController@listExport');
        //买断列表导出
        $api->any('buyoutListExport', 'BuyoutController@listExport');
        /*******************运营数据列表导出--临时***************************/
        $api->any('operator', 'TestExcelController@operator');
        $api->any('everDay', 'TestExcelController@everDay');
        $api->any('everWeek', 'TestExcelController@everWeek');
        $api->any('fiveteen', 'TestExcelController@fiveteen');
        $api->any('everMonth', 'TestExcelController@everMonth');
        $api->any('Month', 'TestExcelController@Month');
        $api->any('otherMonth', 'TestExcelController@otherMonth');
        $api->any('riskMonth', 'TestExcelController@riskMonth');
        $api->any('riskAll', 'TestExcelController@riskAll');
        /*******************逾期数据导出--临时***************************/
        $api->any('overdueDetail', 'TestExcelController@overdueDetail');

        //退款列表导出
        $api->any('refundListExport', 'ReturnController@refundListExport');
        //退换货列表导出
        $api->any('returnListExport', 'ReturnController@returnListExport');
        //换货列表导出
        $api->any('barterListExport', 'ReturnController@barterListExport');
        //换货列表导出
        $api->any('instalmentListExport', 'InstalmentController@instalmentListExport');


        //隊列取消订单
        $api->any('CancelOrder', 'InnerServiceController@cancelOrder');

        //隊列增加订单发货时生成合同
        $api->any('DeliveryContract', 'InnerServiceController@DeliveryContract');
        //隊列增加风控看板信息
        $api->any('OrderRisk', 'InnerServiceController@orderRisk');
        //隊列返回风控 订单押金信息
        $api->any('YajinReduce', 'InnerServiceController@YajinReduce');
        //隊列取消买断支付单
        $api->any('CancelOrderBuyout', 'InnerServiceController@cancelOrderBuyout');
        //区块链推送队列
        $api->any('OrderPushBlock', 'InnerServiceController@orderPushBlock');
        //隊列确认收货订单
        $api->any('DeliveryReceive', 'InnerServiceController@deliveryReceive');
        //隊列确认订单-申请发货
        $api->any('DeliveryApply', 'InnerServiceController@DeliveryApply');


        //预约退款回调接口
        $api->any('appointmentRefund', 'PayController@appointmentRefund');
        //用户逾期列表导出
        $api->any('overDueExport', 'ToolController@overDueExport');

        //缴款记录导出
        $api->any('payIncomeQueryExport', 'PayController@payIncomeQueryExport');
        //出账记录导出
        $api->any('cleanListExport', 'OrderCleaningController@cleanListExport');

        //出账记录导出
        $api->any('listReletExport', 'ReletController@listReletExport');
        //逾期扣款导出
        $api->any('overdueDeductionExport','OverDueDeductionController@overdueDeductionExport');

    /***********************************************************************************************
     * ******************************cron 脚本处理start    heaven********************************
     ***********************************************************************************************/


    // 定时任务 订单取消接口 不加token
        $api->get('cronCancelOrder', 'CronController@cronCancelOrder');
    // 定时任务 订单自动确认收货接口 不加token
        $api->get('cronDeliveryReceive', 'CronController@cronDeliveryReceive');


    // 定时任务 长租订单到期前一个月发送信息
    $api->get('cronOneMonthEndByLong', 'CronController@cronOneMonthEndByLong');
    // 定时任务 长租订单到期前一周发送信息
    $api->get('cronOneWeekEndByLong', 'CronController@cronOneWeekEndByLong');
    // 定时任务 长租订单逾期一个月发送信息
    $api->get('cronOverOneMonthEndByLong', 'CronController@cronOverOneMonthEndByLong');


    // 定时任务 每日执行定时任务-扣款
    $api->get('crontabCreatepay', 'WithholdController@crontabCreatepay');
    // 定时任务 取消买断
    //$api->get('cronCancelOrderBuyout', 'CronController@cronCancelOrderBuyout');
    // 定时任务 还机逾期违约-修改状态
    $api->get('cronGivebackAgedFail', 'CronController@cronGivebackAgedFail');
    // 定时任务 换货确认收货
    $api->get('cronBarterDelivey', 'CronController@cronBarterDelivey');

    // 定时任务 月初发送提前还款短信
    $api->get('cronPrepayment', 'CronController@cronPrepayment');

    // 定时任务 月初发送提前还款短信
    $api->get('cronWithholdMessage', 'CronController@cronWithholdMessage');

    // 定时任务 扣款逾期短信
    $api->get('cronOverdueMessage', 'CronController@cronOverdueMessage');


    // 定时任务 扣款逾期短信
    $api->get('sendMessage', 'ActiveController@sendMessage');

    // 定时任务 获取连续两个月，总共三个月未缴租金的逾期数据
    $api->get('cronOverdueDeductionMessage', 'CronController@cronOverdueDeductionMessage');

    /*************************************************************************************************
     * ******************************cron 脚本处理end   heaven*************************************
     ************************************************************************************************/




});
$api->version('v1', [
    'namespace' => 'App\ClientApi\Controllers',
    'limit' => config('api.rate_limits.access.limit'),
    'expires' => config('api.rate_limits.access.expires'),
    'middleware' => 'api.throttle'
], function($api){
    $api->any('header', 'AuthRefferController@header');

	$apiMap = [
		'third.wechat.jsapi.sign' => 'WechatJsapiController@sign',
		'third.auth.url' => 'ThirdAuthController@getUrl',
		'third.auth.query' => 'ThirdAuthController@query',
	];
	
	$method = request()->input('method');
	if (isset($apiMap[$method])) {
		$api->post('/',  $apiMap[$method]);
	}
	
});

