<?php
/**
 *      author: heaven
 *      验证client，token信息,请求真实api地址信息，并返回数据
 *      date: 2018-06-08
 */
namespace App\ClientApi\Controllers;
use App\Lib\User\User;
use Illuminate\Http\Request;
use App\ClientApi\Controllers;
use App\Lib\Curl;
use App\Lib\ApiStatus;
class AuthRefferController extends Controller{
    /**
     * 校验token信息
     * Author: heaven
     * @param Request $request
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
            //验证通过
            if ($checkInfo){
                $data = config('tripartite.Interior_Order_Request_data');
                $data['method'] =$params['method'];
                $data['params'] = [
                      'user_id'=> $checkInfo[0]['id'],
                      'mobile'=> $checkInfo[0]['mobile'],
                      'username'=>$checkInfo[0]['username'],
                ];
                $header = ['Content-Type: application/json'];
                $info = Curl::post(config('tripartite.API_INNER_URL'), json_encode($data),$header);
                $info =json_decode($info,true);
                if(!is_array($info)  || $info['code']!=0){
                    return response()->json([
                        'code'  =>ApiStatus::CODE_20002,
                        'msg' => "访问接口错误",
                        'data'    =>[]
                    ]);
                }
                return response()->json([
                    'code'  =>ApiStatus::CODE_0,
                    'msg' => "允许访问",
                    'data'    =>$info['data']
                ]);
            }else{
                return response()->json([
                    'code'  =>ApiStatus::CODE_20003,
                    'msg' => "验证错误",
                    'data'    =>[]
                ]);
            }
        }
    }






}


