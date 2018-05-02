<?php

use Illuminate\Http\Request;

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
   