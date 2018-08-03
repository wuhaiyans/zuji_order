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
        $api->post('refundClean', 'PayController@refundClean');

        //订单清算退押金回调接口
        $api->post('unFreezeClean', 'PayController@unFreezeClean');

        $api->any('orderListExport', 'OrderController@orderListExport');
		//还机列表导出
        $api->any('givebackListExport', 'GivebackController@listExport');
        //买断列表导出
        $api->any('buyoutListExport', 'BuyoutController@listExport');
        //退款列表导出
        $api->any('refundListExport', 'ReturnController@refundListExport');
        //退换货列表导出
        $api->any('returnListExport', 'ReturnController@returnListExport');
        //换货列表导出
        $api->any('barterListExport', 'ReturnController@barterListExport');


        //隊列取消订单
        $api->any('CancelOrder', 'InnerServiceController@cancelOrder');
        //隊列取消买断支付单
        $api->any('CancelOrderBuyout', 'InnerServiceController@cancelOrderBuyout');
        //隊列确认收货订单
        $api->any('DeliveryReceive', 'InnerServiceController@deliveryReceive');
        

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
    // 定时任务 每日执行定时任务-扣款
    $api->get('cronCancelOrderBuyout', 'CronController@cronCancelOrderBuyout');
    // 定时任务 还机逾期违约-修改状态
    $api->get('cronGivebackAgedFail', 'CronController@cronGivebackAgedFail');
    // 定时任务 换货确认收货
    $api->get('cronBarterDelivey', 'CronController@cronBarterDelivey');

    // 定时任务 月初发送提前还款短信
    $api->get('cronPrepayment', 'CronController@cronPrepayment');


    /*************************************************************************************************
     * ******************************cron 脚本处理end   heaven*************************************
     ************************************************************************************************/




});
$api->version('v1', [
    'namespace' => 'App\ClientApi\Controllers',
    'limit' => config('api.rate_limits.access.limit'),
    'expires' => config('api.rate_limits.access.expires'),
    'middleware' => 'api'
], function($api){
    $api->any('header', 'AuthRefferController@header');
});
