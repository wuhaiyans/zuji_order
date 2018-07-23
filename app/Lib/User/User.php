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
use App\Lib\Common\LogApi;
use App\Lib\Curl;
use Illuminate\Support\Facades\Log;
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

    public static function getUserAlipayId($user_id){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.user.query.alipayid';
        $data['params'] = [
            'user_id'=>$user_id,
        ];
        //var_dump($data);
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
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
        if($params['zm_face'] == 'Y'){
            $zm_face = 1;
        }else{
            $zm_face = 0;
        }
        if($params['zm_risk'] == 'Y'){
            $zm_risk = 1;
        }else{
            $zm_risk = 0;
        }
        $data['params'] = [
            'mobile'=>$params['mobile'],
            'realname'=>$params['name'],
            'zm_face'=>$zm_face,
            'zm_risk'=>$zm_risk,
            'cert_no'=>$params['cert_no'],
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
    /**
     * 获取用户地址信息
     *  @param $data
     * @param $user_id
     * @return string or array
     */
    public static function getAddressId($params){
        print_r($params);
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.district.query.id';
        $data['params'] = [
            'house'=>$params['house'],
            'name'=>$params['name'],
            'mobile'=>$params['mobile'],
            'user_id'=>$params['user_id'],
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        print_r($info);die;
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }



    /**
     * 检验获取用户token信息
     * Author: heaven
     * @param $token
     * @return bool|mixed
     */
    public static function checkToken($token){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.login.user.info.get';
        $data['auth_token'] =$token;
        $data['params'] = [
        ];
        $header = ['Content-Type: application/json'];
        $list=['url'=>config('tripartite.Interior_Goods_Url'),"data"=>$data];
        Log::debug("checkToken获取用户信息",$list);
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data),$header);
        Log::debug("checkToken返回结果".$info);
        $info = str_replace("\r\n","",$info);
        $info =json_decode($info,true);
        return $info;
    }
}



















