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

class Coupon{

    /**
     * 获取优惠券信息
     * @param $coupon 二维数组
     * 0=>[
     *  'user_id'=>,
     *  'coupon_on'
     * ]
     * @return string
     */
    public static function getCoupon($coupon){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.coupon.rows.get';
        $data['params'] = [
            'coupon'=>$coupon,
        ];
        //var_dump($data);die;
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info = json_decode($info,true);
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
     *  使用优惠券
     * @param $arr[
     * coupon_id
     * ]
     * @return string or array
     */
    public static function useCoupon($arr){
        $data = config('tripartite.Interior_Goods_Request_data');
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
     * @param$arr[
     * $user_id //spu_id
     * $coupon_id //优惠券id
     * ]
     * @return string or array
     */
    public static function setCoupon($data,$arr){
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



















