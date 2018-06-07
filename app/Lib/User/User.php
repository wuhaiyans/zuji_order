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

    public static function getUser($user_id,$address_id=0){
        $data = config('tripartite.Interior_Goods_Request_data');
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

    public static function setRemark($user_id,$remark){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.user.remark.set';
        $data['params'] = [
            'user_id'=>$user_id,
            'order_remark'=>$remark,
        ];
        //var_dump($data);
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        //var_dump($info);die;
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }

    /**
     * 获取用户id生成用户
     *  @param $data
     * @param $user_id
     * @return string or array
     */
    public static function getUserId($params){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.mini.user.id.get';
        $data['params'] = [
            'mobile'=>$params['mobile'],
            'realname'=>$params['name'],
            'zm_face '=>$params['zm_face'],
            'cert_no'=>$params['cert_no'],
        ];
        //var_dump($data);
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        return [
            'user_id'=>'1',
        ];
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }
    /**
     * 获取用户地址信息
     *  @param $data
     * @param $user_id
     * @return string or array
     */
    public static function getAddressId($params){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.district.query.id';
        $data['params'] = [
            'house'=>$params['house'],
        ];
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



















