<?php
/**
 *      author: heaven
 *      验证client，token信息,请求真实api地址信息，并返回数据
 *      date: 2018-06-08
 */
namespace App\ClientApi\Controllers;
use App\Lib\Common\LogApi;
use App\Lib\User\User;
use Illuminate\Http\Request;
use App\ClientApi\Controllers;
use App\Lib\Curl;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Log;

class AuthRefferController extends Controller{
    /**
     * 校验token信息
     * Author: heaven
     * @param Request $request
     *[
     * "appid"    => "1"
     * "version"  => "v1"
     * "auth_token" => ""
     * "method"  =>""
     * "params"=>[]
     * "type"   =>
     * ]
     * @return bool
     */
    public function header(Request $request)
    {
        //默认订单都需要验证，除了主动扣款
        $params = $request->all();
        //是否需要验证
        if (isset($params['auth_token']) && !in_array($params['method'], config('clientAuth.exceptAuth'))) {
            $token  =   $params['auth_token'];
            $checkInfo = User::checkToken($token);
            Log::debug("验证token调用第三方User::checkToken返回的结果".print_r($checkInfo,true));
            //验证通过
            if ($checkInfo){
                $params['userinfo']=[
                    'uid'      =>$checkInfo[0]['id'],
                    'type'     =>2,       //用户类型（固定值1）：1：管理员；2：前端用户
                    'username' =>$checkInfo[0]['mobile']
                ];
                $header = ['Content-Type: application/json'];
                $list=['url'=>config('tripartite.API_INNER_URL'),'data'=>$params];
                Log::debug("验证token",$list);
                $info = Curl::post(config('tripartite.API_INNER_URL'), json_encode($params),$header);
                Log::debug("验证token".$info);
                $info =json_decode($info,true);
                if( is_null($info)
                    || !is_array($info)
                    || !isset($info['code'])
                    || !isset($info['msg'])
                    || !isset($info['data']) ){
                    return response()->json([
                        'code'  =>ApiStatus::CODE_20002,
                        'msg'   => "访问接口错误",
                        'data'  =>$info
                    ]);
                }
                return response()->json([
                    'code'  =>ApiStatus::CODE_0,
                    'msg'   => "允许访问",
                    'data'  =>$info['data']
                ]);
            }else{
                return response()->json([
                    'code'  =>ApiStatus::CODE_20003,
                    'msg'   => "验证错误",
                    'data'  =>$checkInfo
                ]);
            }
        }
    }







}


