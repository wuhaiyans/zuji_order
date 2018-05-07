<?php
/**
 *  下单第三方接口调用类
 *
 * User: wangjinlin
 * Date: 2018/5/7
 * Time: 16:32
 */

namespace App\Warehouse\Modules\Repository;
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

}