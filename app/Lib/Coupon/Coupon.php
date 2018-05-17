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
     * @param $coupon_no 优惠券码
     * @param $user_id
     * @param $payment 商品价格 单位(分)
     * @param $spu_id
     * @param $sku_id
     * @return string
     */
    public static function getCoupon($data,$coupon_no,$user_id,$payment,$spu_id,$sku_id){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.coupon.row.get';
        $data['params'] = [
            'coupon_no'=>$coupon_no,
            'user_id'=>$user_id,
            'payment'=>$payment,
            'spu_id'=>$spu_id,
            'sku_id'=>$sku_id,
        ];
        //var_dump($data);die;
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

    }    /**
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
        //var_dump($data);die;
        $info =json_decode($info,true);
        //var_dump($info);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return ApiStatus::CODE_0;
    }



}



















