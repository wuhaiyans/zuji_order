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
    //'middleware' => 'api'
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
        $api->post('order', 'OrderController@store')
            ->name('api.order.store');
        $api->post('order/confirmation', 'OrderController@confirmation')
        ->name('api.order.confirmation'); //创建订单接口
        $api->post('order/create', 'OrderController@create')
            ->name('api.order.create'); //创建订单接口
        $api->post('order/orderlist', 'OrderController@orderList')
            ->name('api.order.orderlist');//订单列表接口
        $api->post('order/orderdetail', 'OrderController@orderDetail')
            ->name('api.order.orderdetail');//订单列表接口
        $api->post('user', 'UserController@me') //获取用户信息接口
            ->name('api.user.show');
//    });
});
   