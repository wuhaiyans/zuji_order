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

//$api = app('Dingo\Api\Routing\Router');
//
//$api->version('v1', [
//    'namespace' => 'App\Order\Controllers\Api\v1',
//    'limit' => config('api.rate_limits.access.limit'),
//    'expires' => config('api.rate_limits.access.expires'),
//
//], function($api) {
//    $api->post('order', 'OrderController@store')
//    ->name('api.order.store');
//    $api->post('users', 'UserController@store')
//    ->name('api.users.store');
//});
//
//
//$api->version('v1', [
//    'namespace' => 'App\Order\Controllers\Api\v1',
//    'limit' => config('api.rate_limits.access.limit'),
//    'expires' => config('api.rate_limits.access.expires'),
//    'prefix' => 'auth',
//], function($api) {
//
//    $api->post('login', 'AuthController@login');
//    $api->post('logout', 'AuthController@logout');
//});



//Route::prefix('auth')->group(function($router) {
//    $router->post('login', 'AuthController@login');
//    $router->post('logout', 'AuthController@logout');
//
//
//});



//$api->version('v1', function ($api) {
//    $api->group(['middleware' => 'refresh.token'], function ($api) {
//        $api->get('profile','UserController@profile');
//    });
//});


//Route::middleware('refresh.token')->group(function($router) {
//    $router->get('profile','UserController@profile');
//});


$api = app('Dingo\Api\Routing\Router');
$api->version('v1', ['namespace' => 'App\Order\Controllers\Api\v1'], function($api) {
    $api->post('token', 'UserController@token');    //获取token
    $api->post('refresh-token', 'UserController@refershToken'); //刷新token
//    $api->get('me', 'UserController@me');    //关于我
    $api->group(['middleware' => ['auth:api']], function($api) {
        $api->post('logout', 'UserController@logout');    //登出
        $api->post('me', 'UserController@me');    //关于我
    });
});
   