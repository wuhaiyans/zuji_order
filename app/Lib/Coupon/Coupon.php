<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Lib\Coupon;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Coupon extends \App\Lib\BaseApi{

    /**
     * 获取优惠券信息
     * @author wuhaiyan
     * @param $coupon 二维数组
     * 0=>[
     *  'user_id'=>'',//【必须】 string 用户id
     *  'coupon_on'=>''//【必须】string 优惠券码
     * ]
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */
    public static function getCoupon($coupon){

        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.coupon.rows.get', '1.0', ['coupon'=>$coupon]);

    }

    /**
     * 根据用户id获取用户租金抵用券
     * @param $user_id 用户ID
     * @return string
     */
    public static function getUserCoupon($user_id){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.coupon.voucher.get';
        $data['params'] = [
            'user_id'=>$user_id,
        ];

        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info = json_decode($info,true);

        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];

    }
    /**
     *  使用优惠券接口
     * @author wuhaiyan
     * @param $arr[
     *      1,2,3 // 【必须】array 优惠券id
     * ]
     * @return string or array
     */
    public static function useCoupon($arr){
        $data = config('tripartite.Interior_Goods_Request_data');//请求参数信息（版本 ，appid ）
        $data['method'] ='zuji.goods.coupon.status1.set';
        $data['params'] = [
            'coupon_id'=>$arr,
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return ApiStatus::CODE_0;
    }

    /**
     * 优惠券恢复
     * @author wuhaiyan
     * @param  $arr[
     *      $user_id // 【必须】string 用户id
     *      $coupon_id =>[12,23,]//【必须】 array 优惠券id
     * ]
     * @return string or array
     */
    public static function setCoupon($arr){
        $data = config('tripartite.Interior_Goods_Request_data');//请求参数信息（版本 ，appid ）
        $data['method'] ='zuji.goods.coupon.status0.set';
        $data['params'] = [
            'user_id'=>$arr['user_id'],
            'coupon_id'=>$arr['coupon_id'],
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return ApiStatus::CODE_0;
    }



}



















