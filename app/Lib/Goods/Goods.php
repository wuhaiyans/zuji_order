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

class Goods  extends \App\Lib\BaseApi{

    /**
     * 获取商品信息
     * @param array $skuids		SKU ID 数组
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */
    public static function getSkuList( array $skuids ){
        $_params = [];
        foreach( $skuids as $id){
            $_params[] =['sku_id'=>$id];
        }
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.spusku.get', '1.0', ['list_sku_id'=>$_params]);

    }

    /**
     * 获取商品信息
     * @param int $spuId
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */
    public static function getSpuInfo( int $spuId ){
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.spu.get', '1.0', ['id'=>$spuId]);

    }

    /**
     * 获取商品信息
     * @param $data 配置参数
     * @param $sku_id
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */
    public static function getSku( array $skuid ){
        $params = [
            'list_sku_id'=>[],
        ];
        foreach($skuid as $id){
            $params['list_sku_id'][]['sku_id'] = $id;
        }
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.spusku.get', '1.0', $params);
    }
    /**
     * 增加库存
     *  @author wuhaiyan
     * @param $goods_arr
     * "goods_arr": - [                //类型：Array  必有字段  备注：参数集
     *   "spu_id=>1",                //类型：String  必有字段  备注：spu_id
     *   "sku_id=>2",                //类型：String  必有字段  备注：sku_id
     *   "num=>1"                    //类型：String  必有字段  备注：数量
    ]
     * @return bool
     * @throws \Exception			请求失败时抛出异常
     */
    public static function addStock($goods_arr){

        $result =self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.number.add', '1.0', ['goods_arr'=>$goods_arr]);
        if(is_array($result)){
            return true;
        }
        return false;
    }
    /**
     * 减少库存
     * @author wuhaiyan
     * @param $goods_arr
     * "goods_arr": - [                //类型：Array  必有字段  备注：参数集
     *   "spu_id=>1",                //类型：String  必有字段  备注：spu_id
     *   "sku_id=>2",                //类型：String  必有字段  备注：sku_id
     *   "num=>1"                    //类型：String  必有字段  备注：数量
    ]
     * @return bool
     * @throws \Exception			请求失败时抛出异常
     */
    public static function reduceStock($goods_arr){
        try{
            $result =self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.number.minus', '1.0', ['goods_arr'=>$goods_arr]);
            return true;
        }catch (\Exception $e){
            return false;
        }

    }

}



















