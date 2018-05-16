<?php

namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Lib\Payment\FundAuthApi;

class FundauthController extends Controller
{

    // 资金预授权
    public function initialize(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        $params = filter_array($params, [
            'out_auth_no'   => 'required', //订单系统授权码
            'amount'        => 'required', //授权金额；单位：分
            'front_url'     => 'required', //前端回跳地址
            'back_url'      => 'required', //后台通知地址
        ]);
        if(count($params) < 6){
            return false;
        }

        $url = FundAuthApi::authorization($appid, $params);

        return $url;
    }




}
