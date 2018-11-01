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
     * @var int 预定已领取
     */
    const DestineReceive = 3;
    /**
     * @var int 预定已退款
     */
    const DestineRefunded = 4;
    /**
     * @var int 预定退款中
     */
    const DestineRefund = 5;

    //--------------------------------------------------------------------------------------------
    //--+ 活动体验类型 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------

    /**
     * @var int 年度草单
     */
    const ANNUALGRASSSHEET =1;
    /**
     * @var int 头号玩家
     */
    const NumberOnePlayer =2;
    /**
     * @var int 全民焕新
     */
    const TheWholePepole = 3;
    /**
     * @var int 女神养成
     */
    const ExquisiteLife = 4;

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

    //--------------------------------------------------------------------------------------------
    //--+ 租期类型--------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 天
     */
    const Day =1;
    /**
     * @var int 月
     */
    const Month = 2;
    /**
     * @var int 年
     */
    const Year = 3;


    /**
     * 预定状态
     * @return array
     */
    public static function getStatusType(){
        return [
            self::DestineCreated => '未支付',
            self::DestinePayed => '已支付',
            self::DestineReceive => '已领取',
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
            self::ANNUALGRASSSHEET => '年度草单',
            self::NumberOnePlayer => '头号玩家',
            self::TheWholePepole => '全民焕新',
            self::ExquisiteLife => '女神养成',
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
    public static function getExperienceStatusType(){
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


    /**
     * 活动租期类型
     * @return array
     */
    public static function getZuqiTypeStatusType(){
        return [
            self::Day => '天',
            self::Month => '年',
            self::Year => '月',
        ];
    }


    /**
     * 活动租期类型 转换成 租期名称
     * @param int $status   租期
     * @return string 租期名称
     */
    public static function getZuqiTypeStatusTypeName($status){
        $list = self::getZuqiTypeStatusType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
}

