<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */
namespace App\Order\Modules\Inc;

class StoreAddress
{
    /**
     * 获取线下门店地址
     * @param int $appid
     * @return array
     */
    public static function getStoreAddress(int $appid){

        $arr = [
            '139'=>'天津市西青区大学城师范大学南门华木里底商711旁100米,拿趣用数码共享便利店.电话:18611002204',
        ];
        if(isset($arr[$appid])){
            return $arr[$appid];
        }
        return false;
    }

}