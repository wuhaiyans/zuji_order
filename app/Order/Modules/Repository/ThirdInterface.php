<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Order\Modules\Repository;
class ThirdInterface{

    public function GetUser(){
        echo "获取用户信息<br>";
    }
    public function GetSku(){
        echo "获取商品信息<br>";
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



















