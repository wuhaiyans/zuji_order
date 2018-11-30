<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: zhangjinhui
 * Date: 2018/10/22
 * Time: 16:32
 */

namespace App\Lib\Address;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Address extends \App\Lib\BaseApi{

    /**
     * 用户收货地址查询接口（列表）
     * @author zhangjinhui
     * @param  $arr[
     *      user_id =>2//【必须】 string user_id
     * ]
     * @return string or array
     */
    public static function addressQuery($arr){
        $data = config('tripartite.Interior_Goods_Request_data');//请求参数信息（版本 ，appid ）
        $data['method'] ='zuji.user.address.query';
        $data['auth_token'] = $arr['auth_token'];
        $data['params'] = [
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        \App\Lib\Common\LogApi::notify('用户收货地址查询接口（列表）zuji.user.address.query',[
            'request'=>$data,
            'response'=>$info
        ]);
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }

}