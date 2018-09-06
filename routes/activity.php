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
    'namespace' => 'App\Activity\Controllers\Api\v1',
    'limit' => config('api.rate_limits.access.limit'),
    'expires' => config('api.rate_limits.access.expires'),
    'middleware' => 'api'
], function($api) {

    $apiMap = config('apimapactivity');
	$method = request()->input('method');
	if (isset($apiMap[$method])) {
		$api->any('/',  $apiMap[$method]);
	}
    //预订单列表导出
    $api->any('destineExport', 'ActivityDestineController@destineExport');
});


