<?php

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
    'middleware' => 'api.throttle'
], function($api) {

    $apiMap = config('apimapwarehouse');

	$method = request()->input('method');
	if (isset($apiMap[$method])) {
		$api->post('/',  $apiMap[$method]);
	}
});

//
//
//
//$api = app('Dingo\Api\Routing\Router');
//
//$api->version('v1', [
//    'namespace' => 'App\Warehouse\Controllers\Api\v1',
//    'limit' => config('api.rate_limits.access.limit'),
//    'expires' => config('api.rate_limits.access.expires'),
//], function($api) {
//
//    $api->post('token', 'UserController@token');    //获取token
//    $api->post('refresh-token', 'UserController@refershToken'); //刷新token
//
//    $api->post('receive/receivelist', 'ReceiveController@receiveList')
//        ->name('warehouse.receive.receivelist');//订单列表接口
//    $api->post('me', 'UserController@me');
//
//    $api->any('test', 'TestController@test');
//    $api->any('apply', 'TestController@apply');
//
//
//
//    //发货
//    $api->any('warehouse.delivery.cancel', 'DeliveryController@cancel'); //取消发货
//    $api->any('warehouse.delivery.cancelDelivery', 'DeliveryController@cancelDelivery'); //取消发货
//    $api->any('warehouse.delivery.receive', 'DeliveryController@receive'); //签收
//    $api->any('warehouse.delivery.show', 'DeliveryController@show'); //清单
//    $api->any('warehouse.delivery.imeis', 'DeliveryController@imeis'); //对应发货单imei列表
//    $api->any('warehouse.delivery.send', 'DeliveryController@send'); //发货反馈
//    $api->any('warehouse.delivery.logistics', 'DeliveryController@logistics'); //修改快递物流信息
//    $api->any('warehouse.delivery.cancelMatch', 'DeliveryController@cancelMatch'); //取消配货
//    $api->any('warehouse.delivery.addImei', 'DeliveryController@addImei'); //添加imei
//    $api->any('warehouse.delivery.delImei', 'DeliveryController@delImei'); //删除imei
//    $api->any('warehouse.delivery.list', 'DeliveryController@list'); //列表
//    $api->any('warehouse.delivery.refuse', 'DeliveryController@refuse'); //拒签   待完成
//
//
//
//    //收货
//    $api->any('warehouse.receive.list', 'ReceiveController@list'); //列表
//    $api->any('warehouse.receive.create', 'ReceiveController@create'); //创建
//    $api->any('warehouse.receive.cancel', 'ReceiveController@cancel'); //取消
//    $api->any('warehouse.receive.received', 'ReceiveController@received'); //收货
//    $api->any('warehouse.receive.calcelReceive', 'ReceiveController@calcelReceive'); //改变收货状态为未收货
//    $api->any('warehouse.receive.check', 'ReceiveController@check');//验收，针对设备
//    $api->any('warehouse.receive.cancelCheck', 'ReceiveController@cancelCheck');//验收取消，针对设备
//    $api->any('warehouse.receive.finishCheck', 'ReceiveController@finishCheck');//验收完成，针对收货单
//    $api->any('warehouse.receive.show', 'ReceiveController@show');//清单查询，针对收货单
//    $api->any('warehouse.receive.note', 'ReceiveController@note');//录入检测项，针对收货单
//
//
//});
//