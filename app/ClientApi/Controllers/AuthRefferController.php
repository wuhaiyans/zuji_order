<?php
/**
 *      author: heaven
 *      验证client，token信息,请求真实api地址信息，并返回数据
 *      date: 2018-06-08
 */
namespace App\ClientApi\Controllers;

use App\Lib\User\User;

class AuthRefferController extends Controller{

    /**
     * 校验token信息
     * Author: heaven
     * @param Request $request
     * @return bool
     */
    public function headerAction(Request $request)
    {
        //默认订单都需要验证，除了主动扣款
        $params = $request->all();
        //是否需要验证
        if (isset($params['auth_token']) && !in_array($params['method'], config('clientAuth.exceptAuth'))) {
            $token  =   $params['auth_token'];
            $checkInfo = User::checkToken($token);
            //验证通过
            if ($checkInfo['code'] ===0) {
                return $checkInfo['data'];
            } else {
                return $checkInfo;
            }
        }



    }






}


