<?php
/** 电子合同
 *  第三方接口调用类
 * Created by PhpStorm.
 * User: limin
 * Date: 2018/6/7
 * Time: 11:32
 */

namespace App\Lib\Contract;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Contract{

    /**
     * 根据订单编号获取合同信息
     * @param string $orderNo
     * @return array
     */
    public static function getOrderNoContract($orderNo){
        if(!$orderNo){
            return false;
        }
        $info = Curl::post(config('tripartite.Contract_Order_NO_Url'), json_encode(['order_no'=>$orderNo]));
        if(!$info){
            return false;
        }
        $info = json_decode($info,true);
        if(!is_array($info)){
            return false;
        }
        if($info['code']!=0){
            return $info;
        }
        return $info['data'];
    }
    /**
     * 根据订单编号获取合同信息
     * @param string $orderNo
     * @param string $goodsNo
     * @return array
     */
    public static function getGoodsNoContract($orderNo,$goodsNo){
        if(!$orderNo || !$goodsNo){
            return false;
        }
        $info = Curl::post(config('tripartite.Contract_Goods_NO_Url'), json_encode(['order_no'=>$orderNo,'goods_no'=>$goodsNo]));
        if(!$info){
            return false;
        }
        $info = json_decode($info,true);
        if(!is_array($info)){
            return false;
        }
        if($info['code']!=0){
            return $info;
        }
        return $info['data'];
    }
    /**
     * 根据订单编号获取合同信息
     * @param  $params [array]
     * [
     * ’spu_id‘=>'' //【必须】商品id
     * 'order_no' =>''  //【必须】订单号
     * goods_no' =>''  //【必须】商品编号
     * chengse' =>''  //【必须】品类
     * machine_no' =>''  //【必须】机型型号
     * imei' =>''  //【必须】IMEI号
     * zuqi' =>''  //【必须】租期
     * 'zujin' =>''  //【必须】租金
     * 'market_price'=>''//【必须】市场价
     * 'mianyajin' =>''  //【必须】免押金
     * 'yiwaixian' =>''  //【必须】意外险
     * 'user_id' =>''  //【必须】会员id
     * 'name' =>''  //【必须】姓名
     * 'id_cards'=>'' //【必须】身份证号
     * 'mobile'=>'' //【必须】手机号
     * 'address' =>''  //【必须】通讯地址
     * 'delivery_time'=> //【必须】发货时间
     * ]
     * @return array
     */
    public static function createContract($params){
        $rule= [
            'spu_id'=>'required',
            'order_no'=>'required',
            'goods_no'=>'required',
            'chengse'=>'required',
            'machine_no'=>'required',
            'imei' => 'required',
            'zuqi' => 'required',
            'zujin' => 'required',
            'mianyajin' => 'required',
            'yiwaixian' => 'required',
            'market_price'=>'required',
            'user_id' => 'required',
            'name' => 'required',
            'id_cards' => 'required',
            'mobile' => 'required',
            'address' => 'required',
            'delivery_time'=>'required'
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return false;
        }
        $info = Curl::post(config('tripartite.Contract_Create_Url'), json_encode($params));
        if(!$info){
            return false;
        }
        $info = json_decode($info,true);
        if(!is_array($info)){
            return false;
        }
        if($info['code']!=0){
            return $info;
        }
        return true;
    }

}



















