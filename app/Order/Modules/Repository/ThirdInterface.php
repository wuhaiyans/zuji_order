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
    /**
     * 获取用户信息
     * @param $user_id
     * @return string or array
     */

    public function GetUser($user_id,$address_id=0){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.user.get';
        $data['params'] = [
            'user_id'=>18,
            'address_id'=>8,
        ];
        //var_dump($data);
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
    }

    /**
     * 获取商品信息
     * @param $sku_id
     * @return string or array
     */
    public function GetSku($sku){
        var_dump($sku);die;
        $data = config('tripartite.Interior_Goods_Request_data');
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
    public function GetFengkong(){
        echo "获取风控信息<br>";
    }

    /**
     * 获取风控系统的分数
     * @return int
     */
    public function GetCredit(){
        echo "获取信用分";
        return 100;
    }

    /**
     * 获取优惠券信息
     * @param $coupon_no 优惠券码
     * @param $user_id
     * @param $payment 商品价格 单位(分)
     * @param $spu_id
     * @param $sku_id
     * @return string
     */
    public function GetCoupon($coupon_no,$user_id,$payment,$spu_id,$sku_id){
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

    }

    /**
     * 获取渠道信息
     * @param $appid
     * @return string or array
     */
    public function GetChannel($appid){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.channel.appid.get';
        $data['params'] = [
            'appid'=>$appid,
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

 /**
 * 增加库存
 * @param $spu_id
 * @param $sku_id
 * @return string or array
 */
    public function AddStock($spu_id,$sku_id){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.number.add';
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
        return true;
    }
    /**
     * 减少库存
     * @param $spu_id
     * @param $sku_id
     * @return string or array
     */
    public function ReduceStock($spu_id,$sku_id){
        $data = config('tripartite.Interior_Goods_Request_data');
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
    public function GetDeposit($arr){
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


    /**
     * 优惠券恢复
     * @param$arr[
     * $user_id //spu_id
     * $coupon_id //优惠券id
     * ]
     * @return string or array
     */
    public function setCoupon($arr){
        $data = config('tripartite.Interior_Goods_Request_data');
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



















