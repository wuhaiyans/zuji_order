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
    //第一次首月零租金活动
//    public static $coupon_only = '383c8e805a3410e9ee03481d29a7f76f';//小程序优惠券id
    //第二次首月零租金活动
//    public static $coupon_only = 'f9a8c733d5feb2c4a3f29419e4fef562';//小程序优惠券id
    //国庆首月0租金活动
    public static $coupon_only = 'f3eac8c72f693f962b1cdf5543d8e2fb';//小程序优惠券id

    /**
     * 获取优惠券信息
     * @author wuhaiyan
     * @param $coupon 二维数组
     * 0=>[
     *  'user_id'=>'',//【必须】 string 用户id
     *  'coupon_on'=>''//【必须】string 优惠券码
     * ]
     * @param $appid 【必须】int appid
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */
    public static function getCoupon($coupon,$appid){

        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.coupon.rows.get', '1.0', ['coupon'=>$coupon,'appid'=>$appid]);

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
        \App\Lib\Common\LogApi::notify('根据用户id获取用户租金抵用券zuji.coupon.voucher.get',[
            'request'=>$data,
            'response'=>$info
        ]);
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
        \App\Lib\Common\LogApi::notify('使用优惠券接口zuji.goods.coupon.status1.set',[
            'request'=>$data,
            'response'=>$info
        ]);
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
        \App\Lib\Common\LogApi::notify('优惠券恢复zuji.goods.coupon.status0.set',[
            'request'=>$data,
            'response'=>$info
        ]);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return ApiStatus::CODE_0;
    }

    /**
     * 用户领取优惠券接口
     * @author zhangjinhui
     * @param  $arr[
     *      user_id // 【必须】string 用户id
     *      only_id=>1//【必须】 string 优惠券类型唯一ID
     * ]
     * @return string or array
     */
    public static function drawCoupon($arr){
        $data = config('tripartite.Interior_Goods_Request_data');//请求参数信息（版本 ，appid ）
        $data['method'] ='zuji.mini.coupon.set';
        $data['params'] = [
            'user_id'=>$arr['user_id'],
            'only_id'=>$arr['only_id'],
            'appid'  =>$arr['appid'],
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));

        $info =json_decode($info,true);
        \App\Lib\Common\LogApi::notify('用户领取优惠券接口zuji.mini.coupon.set',[
            'request'=>$data,
            'response'=>$info
        ]);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return ApiStatus::CODE_0;
    }

    /**
     * 查询优惠券
     * @author zhangjinhui
     * @param  $arr[
     *      user_id // 【必须】string 用户id
     *      spu_id =>1//【必须】 string spuid
     *      sku_id =>2//【必须】 string skuid
     *      payment =>32100//【必须】 string 商品价格
     * ]
     * @return string or array
     */
    public static function queryCoupon($arr){
        $data = config('tripartite.Interior_Goods_Request_data');//请求参数信息（版本 ，appid ）
        $data['method'] ='zuji.mini.coupon.get';
        $data['params'] = [
            'user_id'=>$arr['user_id'],
            'spu_id'=>$arr['spu_id'],
            'sku_id'=>$arr['sku_id'],
            'payment'=>$arr['payment']*100,
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        \App\Lib\Common\LogApi::notify('小程序查询优惠券接口zuji.mini.coupon.get',[
            'request'=>$data,
            'response'=>$info
        ]);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }


    /**
     * 查询优惠券
     * @author zhangjinhui
     * @param  $arr[
     *      sku_id =>2//【必须】 string skuid
     *      'appid'=>''//【必须】int appid
     * ]
     * @return string or array
     */
    public static function checkedCoupon($arr){
        $data = config('tripartite.Interior_Goods_Request_data');//请求参数信息（版本 ，appid ）
        $data['method'] ='zuji.coupon.checked';
        $data['auth_token'] = $arr['auth_token'];
        $data['params'] = [
            'sku_id'=>$arr['sku_id'],
            'appid'=>$arr['appid'],
        ];
    //    $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info ='{
  "code": "0",
  "msg": "",
  "sub_code": "",
  "sub_msg": "",
  "data": [
    {
      "coupon_id": "508",
      "coupon_type_id": "7",
      "coupon_no": "241cbb9248a5010a",
      "status": "1",
      "start_time": "1514736000",
      "end_time": "1517328000",
      "user_id": "85051",
      "coupon_name": "6000元抵用券",
      "coupon_type": "1",
      "coupon_value": "600000",
      "use_restrictions": "0"
    },
    {
      "coupon_id": "47123",
      "coupon_type_id": "6",
      "coupon_no": "c80eb0dee581c0bd",
      "status": "1",
      "start_time": "1541433600",
      "end_time": "1543507200",
      "user_id": "85051",
      "coupon_name": "100元抵用券",
      "coupon_type": "1",
      "coupon_value": "10000",
      "use_restrictions": "0"
    }
  ],
  "sign": ""
}';
        $info =json_decode($info,true);
        \App\Lib\Common\LogApi::notify('优惠券商品可用列表查询接口zuji.coupon.checked',[
            'request'=>$data,
            'response'=>$info
        ]);
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }
}



















