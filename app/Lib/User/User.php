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
class User extends \App\Lib\BaseApi{


    /**
     * 获取用户信息
     * @author wuhaiyan
     * @param $user_id //【必须】 用户id
     * @param $address_id //【可选】 用户地址id  如果为 0 则不查询地址信息
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */

    public static function getUser($user_id,$address_id=0){
        $params= [
            'user_id'=>$user_id,
            'address_id'=>$address_id,
        ];

        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.user.get', '1.0', $params);
    }

    /**
     * 获取用户的支付宝信息
     * @author wuhaiyan
     * @param $user_id //【必须】string 用户id
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */

    public static function getUserAlipayId($user_id){

        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.user.query.alipayid', '1.0',['user_id'=>$user_id]);
    }

    /**
     * 设置不能下单原因到用户信息表中
     * @author wuhaiyan
     * @param $user_id  //【必须】 string 用户id
     * @param $remark  //【必须】string 无法下单原因
     * @return bool
     */

    public static function setRemark($user_id,$remark){
        $params = [
            'user_id'=>$user_id,
            'order_remark'=>$remark,
        ];

        $result = self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.user.remark.set', '1.0',$params);
        if(!is_array($result)){
            return false;
        }
        return true;

    }

    /**
     * 小程序获取用户id生成用户
     * @author zhanhgjinhui
     * @param $params
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
        if(!is_array($info)){
            return false;
        }
        if($info['code']!=0){
            return false;
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
        $data['method'] ='zuji.login.user.info.get';//获取用户信息接口
        $data['auth_token'] =$token; //登录用户token
        $data['params'] = [
        ];
        $header = ['Content-Type: application/json'];
        $list=['url'=>config('tripartite.Interior_Goods_Url'),"data"=>$data];
        Log::debug("checkToken获取用户信息".$data['method'],$list);
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data),$header);//通过token获取用户信息
        Log::debug("checkToken返回结果".$info);
        $info = str_replace("\r\n","",$info);
        $info =json_decode($info,true);
        return $info;
    }
}



















