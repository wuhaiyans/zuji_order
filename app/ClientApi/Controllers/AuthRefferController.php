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
     * "appid"       => "1"   渠道id
     * "version"     => "v1"  版本
     * "auth_token"  => ""    token值
     * "method"      =>""     接口方法
     * "params"      =>[]     参数
     * ]
     */
    public function header(Request $request)
    {
        try{
            //默认订单都需要验证，除了主动扣款
            $params = $request->all();
            $header = ['Content-Type: application/json'];
            //是否需要验证
            if(in_array($params['method'], config('clientAuth.exceptAuth'))){
                $info = Curl::post(config('ordersystem.ORDER_API'), json_encode($params),$header);
                LogApi::debug("无需登录直接转发接口信息及结果",[
                    'url' => config('ordersystem.ORDER_API'),
                    'request' => $params,
                    'response' => $info,
                ]);
                $info =json_decode($info,true);
                if( is_null($info)
                    || !is_array($info)
                    || !isset($info['code'])
                    || !isset($info['msg'])
                    || !isset($info['data']) ){
                    return response()->json([
                        'code'  =>ApiStatus::CODE_20002,
                        'msg'   => "稍候重试",
                        'data'  =>$info
                    ]);
                }
                return response()->json($info);
            }elseif(isset($params['auth_token']) && !in_array($params['method'], config('clientAuth.exceptAuth'))) {
                $token     = $params['auth_token'];
                $checkInfo = User::checkToken($token);
                LogApi::debug("验证token调用第三方User::checkToken返回的结果",$checkInfo);
                //验证不通过
                if (is_null($checkInfo)
                    || !is_array($checkInfo)
                    || !isset($checkInfo['code'])
                    || !isset($checkInfo['msg'])
                    || !isset($checkInfo['data'])
                    || $checkInfo['code']!=0){
                    return response()->json($checkInfo);
                }else{

                    $params['userinfo']=[
                        'uid'      =>$checkInfo['data'][0]['id'],
                        'type'     =>2,       //用户类型（固定值1）：1：管理员；2：前端用户
                        'username' =>$checkInfo['data'][0]['mobile']
                    ];
                    $list=['url'=>config('ordersystem.ORDER_API'),'data'=>$params];
                    LogApi::debug("通过登录转发接口的url及参数",[
                        'url'=>config('ordersystem.ORDER_API'),
                        'request' => $params,
                    ]);
                    LogApi::debug("通过登录转发接口的参数",$params);
                    $info = Curl::post(config('ordersystem.ORDER_API'), json_encode($params),$header);
                    $info =json_decode($info,true);
                    LogApi::debug("登录转发接口结果",$info);
                    if( is_null($info)
                        || !is_array($info)
                        || !isset($info['code'])
                        || !isset($info['msg'])
                        || !isset($info['data']) ){

                        if (isset($info['status_code']) && $info['status_code']==429) {
                            return response()->json([
                                'code'  =>ApiStatus::CODE_20002,
                                'msg'   => "操作频率过快，请稍后再试",
                                'data'  =>[]
                            ]);
                        } else {

                            return response()->json([
                                'code'  =>ApiStatus::CODE_20002,
                                'msg'   => "稍候重试",
                                'data'  =>$info
                            ]);

                        }


                    }
                    return response()->json($info);

                }
            }else{
                return response()->json([
                    'code'  =>ApiStatus::CODE_20003,
                    'msg'   => "未登录",
                    'data'  =>[''=>'']
                ]);
            }

        } catch (\Exception $e){

            return response()->json([
                'code'  => -1,
                'msg'   => $e->getMessage(),
                'data'  =>[''=>'']
            ]);

        }

    }







}


