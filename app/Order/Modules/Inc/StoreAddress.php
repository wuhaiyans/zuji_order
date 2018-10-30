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
            '139'=>' *** 大学 ** 线下门店地址',
        ];
        if(isset($arr[$appid])){
            return $arr[$appid];
        }
        return false;
    }

}