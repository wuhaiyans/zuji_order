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




    $api->any('warehouse.delivery.cancel', 'DeliveryController@cancel'); //取消发货
    $api->any('warehouse.delivery.receive', 'DeliveryController@receive'); //签收
    $api->any('warehouse.delivery.show', 'DeliveryController@show'); //清单
    $api->any('warehouse.delivery.imeis', 'DeliveryController@imeis'); //对应发货单imei列表
    $api->any('warehouse.delivery.send', 'DeliveryController@send'); //发货反馈
    $api->any('warehouse.delivery.logistics', 'DeliveryController@logistics'); //修改快递物流信息
    $api->any('warehouse.delivery.cancelMatch', 'DeliveryController@cancelMatch'); //取消配货
    $api->any('warehouse.delivery.addImei', 'DeliveryController@addImei'); //添加imei
    $api->any('warehouse.delivery.delImei', 'DeliveryController@delImei'); //删除imei



    $api->any('warehouse.delivery.list', 'DeliveryController@list'); //列表











});
   