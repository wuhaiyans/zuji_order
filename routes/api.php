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
], function($api) {
    $api->post('order', 'OrderController@store')
    ->name('api.order.store');
    $api->post('order/create', 'OrderController@create')
        ->name('api.order.create'); //创建订单接口
    $api->post('order/orderlist', 'OrderController@orderList')
        ->name('api.order.orderlist');//订单列表接口
    $api->post('order/orderdetail', 'OrderController@orderDetail')
        ->name('api.order.orderdetail');//订单列表接口
    $api->post('users', 'UsersController@store')
    ->name('api.users.store');
});
   