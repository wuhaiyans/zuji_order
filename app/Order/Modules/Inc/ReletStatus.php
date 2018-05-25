<?php

/**
 * ReletStatus 续租状态
 *
 * @author wangjinlin
 */

namespace App\Order\Modules\Inc;


class ReletStatus {

    //--------------------------------------------------------------------------------------------
    //--+ 长租租期 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 3个月
     */
    const CHANGZU3 = 3;

    /**
     * @var int 6个月
     */
    const CHANGZU6 = 6;

    /**
     * @var int 9个月
     */
    const CHANGZU9 = 9;

    /**
     * @var int 12个月
     */
    const CHANGZU12 = 12;
    //--------------------------------------------------------------------------------------------
    //--+ 短租租期 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 3天
     */
    const DUANZU3 = 3;
    /**
     * @var int 7天
     */
    const DUANZU7 = 7;
    /**
     * @var int 15天
     */
    const DUANZU15 = 15;
    /**
     * @var int 30天
     */
    const DUANZU30 = 30;

    /**
     * 获取长租月数选项
     *
     * @return array
     */
    public static function getCangzulist(){
        return [
            self::CHANGZU3,
            self::CHANGZU6,
            self::CHANGZU9,
            self::CHANGZU12,
        ];
    }

    public static function getDuanzuList(){
        return [
            self::DUANZU3,
            self::DUANZU7,
            self::DUANZU15,
            self::DUANZU30,
        ];
    }


    /*
     * 类型转换汉字
     */
//    public static function get_coupon_type_name(int $num):string {
//        $type_arr = [
//            self::CouponTypeFixed=>'固定金额',
//            self::CouponTypePercentage=>'租金百分比',
//            self::CouponTypeFirstMonthRentFree=>'首月0租金',
//            self::CouponTypeDecline=>'租金递减类型',
//        ];
//        return $type_arr[$num];
//    }

    /*
     * 转换状态
     */
//    public static function get_coupon_status_name(int $num):string {
//        $name_arr = [
//            self::CouponStatusNotUsed=>'未使用',
//            self::CouponStatusAlreadyUsed=>'已使用',
//        ];
//        return $name_arr[$num];
//    }

}
