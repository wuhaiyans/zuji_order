<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Lib\User;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class User{
    /**
     * 获取用户信息
     *  @param $data 配置参数
     * @param $user_id
     * @return string or array
     */

    public static function getUser($data,$user_id,$address_id=0){
        $data['method'] ='zuji.goods.user.get';
        $data['params'] = [
            'user_id'=>$user_id,
            'address_id'=>$address_id,
        ];
        //var_dump($data);
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }


}



















