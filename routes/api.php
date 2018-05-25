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
    'middleware' => 'api'
], function($api) {
    $api->post('token', 'UserController@token');    //获取token
    $api->post('refresh-token', 'UserController@refershToken'); //刷新token

    $api->post('trade/notify', 'TradeController@notify'); //支付回调接口

   // $api->group(['middleware' => ['auth:api']], function($api) {

        $apiMap = config('apimap');
        $method = request()->input('method');
        if (isset($apiMap[$method])) {
            $api->post('/',  $apiMap[$method]);
        }

        $api->post('user', 'UserController@me') //获取用户信息接口
            ->name('api.user.show');

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


});
   