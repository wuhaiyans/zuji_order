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
        try{

            //默认订单都需要验证，除了主动扣款
            $params = $request->all();
            $header = ['Content-Type: application/json'];
            //是否需要验证
            if(in_array($params['method'], config('clientAuth.exceptAuth'))){
                $info = Curl::post(config('ordersystem.ORDER_API'), json_encode($params),$header);
                Log::debug("验证token".$info);
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
                $token  =   $params['auth_token'];
                $checkInfo = User::checkToken($token);
                Log::debug("验证token调用第三方User::checkToken返回的结果".print_r($checkInfo,true));
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
                    Log::debug("验证token",$list);
                    $info = Curl::post(config('ordersystem.ORDER_API'), json_encode($params),$header);
                    Log::debug("验证token".$info);
                    $info =json_decode($info,true);
                    LogApi::debug("转发接口信息及结果",[
                        'url' => $list,
                        'request' => $params,
                        'response' => $info,
                    ]);
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


