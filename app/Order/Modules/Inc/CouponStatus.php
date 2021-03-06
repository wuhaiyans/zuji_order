<?php

/**
 * 优惠券状态
 * @access public
 * @author wangjinlin
 * @copyright (c) 2017, Huishoubao
 *
 */

namespace App\Order\Modules\Inc;

/**
 * CouponStatus 订单状态类
 *
 * @author wangjinlin
 */
class CouponStatus {

    //--------------------------------------------------------------------------------------------
    //--+ 优惠类型 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 固定金额
     */
    const CouponTypeFixed = 1;

    /**
     * @var int 租金百分比
     */
    const CouponTypePercentage = 2;

    /**
     * @var int 首月0租金
     */
    const CouponTypeFirstMonthRentFree = 3;

    /**
     * @var int 租金递减类型
     */
    const CouponTypeDecline = 4;
    /**
     * @var int 租金抵用券
     */
    const CouponZujinOffset = 5;
    /**
     * @var int 满减
     */
    const CouponFullSubtraction  = 6;
    //--------------------------------------------------------------------------------------------
    //--+ 优惠范围 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 0全场
     */
    const RangeAll = 0;
    /**
     * @var int 商品
     */
    const RangeSpu = 1;
    /**
     * @var int 2新机
     */
    const RangeNew = 2;
    /**
     * @var int 3二手机
     */
    const RangeOld = 3;
    /**
     * @var int 4手机类别
     */
    const RangeType = 4;
    /**
     * @var int 5渠道
     */
    const RangeChannel = 5;
    /**
     * @var int 规格
     */
    const RangeSku = 6;
    //--------------------------------------------------------------------------------------------
    //--+ 优惠方式 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 1直减
     */
    const ModeReduction = 1;
    /**
     * @var int 2返现
     */
    const ModeCashback = 2;
    //--------------------------------------------------------------------------------------------
    //--+ 使用限制 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 0不限制
     */
    const UseRestrictionsNo = 0;
    /**
     * @var int 1限制
     */
    const UseRestrictionsYes = 1;

    //--------------------------------------------------------------------------------------------
    //--+ 优惠码状态 ------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 0未使用
     */
    const CouponStatusNotUsed = 0;
    /**
     * @var int 1已使用
     */
    const CouponStatusAlreadyUsed = 1;

    //--------------------------------------------------------------------------------------------
    //--+ 优惠类型 作用在订单和商品 ---------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    // first：首月0租金；avg：优惠券优惠(订单优惠券固定金额) serialize:分期顺序优惠
    const CouponTypeFirst ='first';
    const CouponTypeAvg='avg';
    const CouponTypeSerialize ='serialize';




    //--------------------------------------------------------------------------------------------
    //--+ 生成一个无门槛优惠券时间限制 ---------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var string
     */
    const CouponStartDate = '2018-04-16';
    /**
     * @var string
     */
    const CouponEndDate = '2018-05-26';

    /*
     * 类型转换汉字
     */
    public static function get_coupon_type_name(int $num):string {
        $type_arr = [
            self::CouponTypeFixed=>'固定金额',
            self::CouponTypePercentage=>'租金百分比',
            self::CouponTypeFirstMonthRentFree=>'首月0租金',
            self::CouponTypeDecline=>'租金递减类型',
        ];
        return $type_arr[$num];
    }

    /*
     * 转换优惠券状态
     */
    public static function get_coupon_status_name(int $num):string {
        $name_arr = [
            self::CouponStatusNotUsed=>'未使用',
            self::CouponStatusAlreadyUsed=>'已使用',
        ];
        return $name_arr[$num];
    }

}
