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

    //--------------------------------------------------------------------------------------------
    //--+ 活动体验类型 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 头号玩家
     */
    const NumberOnePlayer =0;
    /**
     * @var int 全民焕新
     */
    const TheWholePepole = 1;
    /**
     * @var int 精致生活
     */
    const ExquisiteLife = 2;

    //--------------------------------------------------------------------------------------------
    //--+ 活动类型 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 1元体验活动
     */
    const ExperienceActivity =1;



    //--------------------------------------------------------------------------------------------
    //--+ 活动状态--------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 已约满
     */
    const BeAlreadyFull =0;
    /**
     * @var int 预约体验
     */
    const ReservationExperience = 1;




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

    /**
     * 活动体验类型
     * @return array
     */
    public static function getActivityType(){
        return [
            self::NumberOnePlayer => '头号玩家',
            self::TheWholePepole => '全民焕新',
            self::ExquisiteLife => '精致生活',
        ];
    }


    /**
     * 活动体验状态值 转换成 状态名称
     * @param int $status   活动体验状态值
     * @return string 活动体验状态名称
     */
    public static function getActivityTypeName($status){
        $list = self::getActivityType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


    /**
     * 活动类型
     * @return array
     */
    public static function getExperienceActivityStatusType(){
        return [
            self::ExperienceActivity => '1元体验活动',
        ];
    }


    /**
     * 活动体验状态值 转换成 状态名称
     * @param int $status   活动类型
     * @return string 活动类型名称
     */
    public static function getExperienceActivityStatusName($status){
        $list = self::getExperienceActivityStatusType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    /**
     * 活动类型
     * @return array
     */
    public static function getExperiencetatusType(){
        return [
            self::BeAlreadyFull => '已约满',
            self::ReservationExperience => '预约体验',
        ];
    }


    /**
     * 活动体验状态值 转换成 状态名称
     * @param int $status   活动类型
     * @return string 活动类型名称
     */
    public static function getExperienceStatusName($status){
        $list = self::getExperienceStatusType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
}

