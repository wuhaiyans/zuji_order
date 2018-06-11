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
        $params = $request->input('params');
        $params['auth_token']='501aee08-eb85-4068-b965-28e298e72528_true';
        $params['method']='api.Order.confirmation';
        //是否需要验证
        if (isset($params['auth_token']) && !in_array($params['method'], config('clientAuth.exceptAuth'))) {
            $token  =   $params['auth_token'];
            $checkInfo = User::checkToken($token);
            //验证通过
            if ($checkInfo['code'] ===0){
                $data = config('tripartite.Interior_Order_Request_data');
                $data['method'] =$params['method'];
                $data['params'] = [
                    'user_id'=>$token,
                    'appid'=>$token,
                    'mobile'=>$token,
                ];
                $info = Curl::post(config('tripartite.API_INNER_URL'), json_encode($data));
                $info =json_decode($info,true);
                if(!is_array($info)  || $info['code']!=0){
                    return false;
                }
                return $info['data'];
            } else {
                return $checkInfo;
            }
        }
    }






}


