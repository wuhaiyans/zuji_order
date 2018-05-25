<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('test/{action}', function(App\Http\Controllers\TestController $controller, $action){
    return $controller->$action();
});
Route::get('order/{action}', function(App\Order\Controllers\Api\v1\OrderController $index, $action){
    return $index->$action();
});
Route::any('order/pay/{action}', function(App\Order\Controllers\Api\v1\PayController $index, $action){
    return $index->$action();
});

Route::any('common/pay/{action}', function(App\Common\Controllers\Api\v1\PayController $index, $action){
    return $index->$action();
});

Route::get('users/{action}', function(App\Order\Controllers\Api\v1\UsersController $index, $action){
    return $index->$action();
});

Route::get('return/{action}', function(App\Order\Controllers\Api\v1\ReturnController $index, $action){
    return $index->$action();
});
//还机的回调

Route::any('order/giveback/{action}', function(App\Order\Controllers\Api\v1\GivebackController $index, $action){
    return $index->$action();
});