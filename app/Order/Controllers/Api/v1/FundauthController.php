<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Lib\Payment\FundAuthApi;

class FundauthController extends Controller
{

    /*
     * 资金预授权
     * @request array
     * [
     *      'appid'     => '' //渠道ID
     *      'params'    => [
     *          'amount'        => '' // 授权金额；单位：分
     *          'front_url'     => '' // 前端回跳地址
     *      ]
     * ]
     * return String $url  预授权地址
     */
    public function initialize(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        $params = filter_array($params, [
            'amount'        => 'required', //授权金额；单位：分
            'front_url'     => 'required', //前端回跳地址
            'name'          => 'required', //预授权名称
            'user_id'       => 'required', //用户ID
        ]);
        if(count($params) < 4){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $params = [
            'out_auth_no'   => createNo(4),         //订单系统授权码
            'amount'        => $params['amount'],   //授权金额；单位：分
            'front_url'     => $params['amount'],   //前端回跳地址
            'back_url'      => 'api.Fundauth.initializeNotify', //后台通知地址
            'name'          => $params['name'],     //预授权名称
            'user_id'       => $params['user_id'],  //用户ID
        ];

        $data = FundAuthApi::fundauthUrl($appid, $params);
        $url = !empty($data['authorization_url']) ? $data['authorization_url'] : "";
        return apiResponse(['url'=>$url],ApiStatus::CODE_0,"success");
    }

    /*
     * 资金预授权回调接口
     * @request array
     * [
     *      'appid'     => '' //渠道ID
     *      'params'    => [
     *          'amount'        => '' // 授权金额；单位：分
     *          'front_url'     => '' // 前端回跳地址
     *      ]
     * ]
     * return String $url  预授权地址
     */
    public function initializeNotify(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];
        p($params);
    }

    /*
    * 预授权状态查询接口
    * @request array
    * [
    *      'appid'     => '' //渠道ID
    *      'params'    => [
    *          'amount'        => '' // 授权金额；单位：分
    *          'front_url'     => '' // 前端回跳地址
    *      ]
    * ]
    * return String $url  预授权地址
    */
    public function fundauthQuery(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        $params = filter_array($params, [
            'auth_no'        => 'required', //支付系统授权码
        ]);
        if(count($params) < 1){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $params = [
            'auth_no'   => createNo(4), //订单系统授权码
        ];

        if(count($params) < 4){
            return false;
        }

        $url = FundAuthApi::authorizationStatus($appid, $params);
        p($url);
        return $url;
    }


}
