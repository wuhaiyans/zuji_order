<?php
namespace App\Order\Modules\Inc;

class OrderPayWithholdStatus{

    /**
     * @var int 已签约
     */
    const SIGN = 1;
    /**
     * @var int 已解约
     */
    const UNSIGN = 2;

    /**
     * 获取签约代扣状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
            self::SIGN      => '已签约',
            self::UNSIGN    => '已解约',
        ];
    }

    /**
     * 状态值 转换成 状态名称
     * @param int $status   状态值
     * @return string 状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}

