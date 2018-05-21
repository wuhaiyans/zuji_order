<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Lib\Goods;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Goods{

    /**
     * 获取商品信息
     * @param $data 配置参数
     * @param $sku_id
     * @return string or array
     */
    public static function getSku($data,$sku){
        $data['method'] ='zuji.goods.spusku.get';
        $data['params'] = [
            'list_sku_id'=>$sku,
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
       // var_dump($info);die;
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];

    }
    /**
     * 增加库存
     * heaven
     * @param $goods_arr
     "goods_arr": - [                //类型：Array  必有字段  备注：参数集
        "spu_id=>1",                //类型：String  必有字段  备注：spu_id
        "sku_id=>2",                //类型：String  必有字段  备注：sku_id
        "num=>1"                    //类型：String  必有字段  备注：数量
     ]
     * @return string or array
     */
    public static function addStock($data,$goods_arr){
        $data['method'] ='zuji.goods.number.add';
        $data['params'] = [
            'goods_arr'=>$goods_arr
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);

        //var_dump($info);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return true;
    }
    /**
     * 减少库存
     * @param $spu_id
     * @param $sku_id
     * @return string or array
     */
    public static function reduceStock($data,$spu_id,$sku_id){
        $data['method'] ='zuji.goods.number.minus';
        $data['params'] = [
            'spu_id'=>$spu_id,
            'sku_id'=>$sku_id
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        //var_dump($info);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }

}


















