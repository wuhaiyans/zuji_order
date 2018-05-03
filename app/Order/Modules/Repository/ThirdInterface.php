<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class ThirdInterface{

    public function GetUser($user_id){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.user.get';
        $data['params'] = [
            'user_id'=>18,
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        var_dump($info);
        if($info['code']!=0){
            return ApiStatus::CODE_60001;
        }
        return $info['data'];
    }
    public function GetSku($sku_id){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.spusku.get';
        $data['params'] = [
            'sku_id'=>$sku_id,
        ];
        $good_info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $good_info =json_decode($good_info,true);
        var_dump($good_info);
        if($good_info['code']!=0){
            return ApiStatus::CODE_60001;
        }
        return $good_info['data'];

    }
    public function GetFengkong(){
        echo "获取风控信息<br>";
    }
    public function GetCredit(){
        var_dump('获取用户信用');
    }

    public function GetCoupon(){
        var_dump('获取优惠券');
    }

}



















