<?php
namespace App\Order\Modules\Inc;

/**
 * appid 类型值
 * Class AppIdInc
 * @package App\Order\Modules\Inc
 */


class AppIdInc{

    const TYPE_H5 = 1;
    const TYPE_API = 2;
    const TYPE_STORE = 3;   //线下门店
    const TYPE_ALI_ZHIMA = 4;   //支付宝小程序

    public $enum_type = [
        self::TYPE_H5 => 'H5',
        self::TYPE_API => 'openapi',
        self::TYPE_STORE => '线下门店',
        self::TYPE_ALI_ZHIMA => '支付宝小程序'
    ];


    /**
     * appid类型列表
     * @return array
     */
    public static function getAppIdTypeList(){
        return [
            self::TYPE_H5 => 'H5',
            self::TYPE_API => 'openapi',
            self::TYPE_STORE => '线下门店',
            self::TYPE_ALI_ZHIMA => '支付宝小程序'
        ];
    }

    /**
     * 获取appid类型名称
     * @param int $appType
     * @return string app类型名称
     */
    public static function getPayChannelName($appType){
        $list = self::getAppIdTypeList();
        if( isset($list[$appType]) ){
            return $list[$appType];
        }
        return '';
    }


}