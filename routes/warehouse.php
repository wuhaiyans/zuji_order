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
    'namespace' => 'App\Warehouse\Controllers\Api\v1',
    'limit' => config('api.rate_limits.access.limit'),
    'expires' => config('api.rate_limits.access.expires'),
], function($api) {

    $api->post('token', 'UserController@token');    //获取token
    $api->post('refresh-token', 'UserController@refershToken'); //刷新token

    $api->post('receive/receivelist', 'ReceiveController@receiveList')
        ->name('warehouse.receive.receivelist');//订单列表接口
    $api->post('me', 'UserController@me');

    $api->any('test', 'TestController@test');
    $api->any('apply', 'TestController@apply');
});
   