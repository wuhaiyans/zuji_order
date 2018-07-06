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
     * @return string or array
     */
    public static function getSku( array $skuid ){
		$params = [
            'list_sku_id'=>[],
		];
		foreach($skuid as $id){
			$params['list_sku_id'][]['sku_id'] = $id;
		}
		return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.spusku.get', '1.0', $params);
		
		
//        $data['method'] ='zuji.goods.spusku.get';
//        $data['params'] = [
//            'list_sku_id'=>$sku,
//        ];
//        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
//        $info =json_decode($info,true);
//       // var_dump($info);die;
//        if(!is_array($info)){
//            return ApiStatus::CODE_60000;
//        }
//        if($info['code']!=0){
//            return $info['code'];
//        }
//        return $info['data'];

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
    public static function addStock($goods_arr){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.number.add';
        $data['params'] = [
            'goods_arr'=>$goods_arr
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        //var_dump($info);
        if(!is_array($info)){
            return false;
        }
        if($info['code']!=0){
            return false;
        }
        return true;
    }
    /**
     * 减少库存
     * @param $goods_arr
        "goods_arr": - [                //类型：Array  必有字段  备注：参数集
        "spu_id=>1",                //类型：String  必有字段  备注：spu_id
        "sku_id=>2",                //类型：String  必有字段  备注：sku_id
        "num=>1"                    //类型：String  必有字段  备注：数量
    ]
     * @return string or array
     */
    public static function reduceStock($goods_arr){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.number.minus';
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

}



















