<?php
/**
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/4/27
 * Time: 11:26
 */
namespace App\Activity\Modules\Inc;

class DestineStatus{

    //--------------------------------------------------------------------------------------------
    //--+ 预定状态 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 不可使用
     */
    const DestineInitialize =0;
    /**
     * @var int 创建未支付
     */
    const DestineCreated = 1;
    /**
     * @var int 预定已支付
     */
    const DestinePayed = 2;
    /**
     * @var int 预定已退款
     */
    const DestineRefunded = 4;
    /**
     * @var int 预定退款中
     */
    const DestineRefund = 5;

    //--------------------------------------------------------------------------------------------
    //--+ 活动体验状态 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 不可使用
     */
    const ExperienceDestineInitialize =0;
    /**
     * @var int 创建未支付
     */
    const ExperienceDestineCreated = 1;
    /**
     * @var int 预定已支付
     */
    const ExperienceDestinePayed = 2;

    /**
     * 预定状态
     * @return array
     */
    public static function getStatusType(){
        return [
            self::DestineCreated => '未支付',
            self::DestinePayed => '已支付',
            self::DestineRefunded => '已退款',
            self::DestineRefund => '退款中',
        ];
    }

    /**
     * 预定状态值 转换成 状态名称
     * @param int $status   预定状态值
     * @return string 预定状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    /**
     * 活动体验状态
     * @return array
     */
    public static function getEDStatusType(){
        return [
            self::DestineCreated => '未支付',
            self::ExperienceDestinePayed => '已支付',
        ];
    }


    /**
     * 活动体验状态值 转换成 状态名称
     * @param int $status   活动体验状态值
     * @return string 活动体验状态名称
     */
    public static function getEDStatusName($status){
        $list = self::getEDStatusType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}

