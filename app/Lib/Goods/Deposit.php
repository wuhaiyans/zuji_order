<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Lib\Deposit;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Deposit{

    /**
     * 获取支付押金接口
     * @param$arr[
     * $spu_id //spu_id
     * $pay_type //支付类型
     * $credit //信用分
     * $age //年龄
     * $yajin //商品押金
     * ]
     * @return string or array
     */
    public static function getDeposit($data,$arr){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.rule.get';
        $data['params'] = [
            'spu_id'=>$arr['spu_id'],
            'payment_type_id'=>$arr['pay_type'],
            'credit'=>$arr['credit'],
            'age'=>$arr['age'],
            'yajin'=>$arr['yajin']
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
        return $info['data'];
    }



}



















