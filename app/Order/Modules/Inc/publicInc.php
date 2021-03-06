<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Inc;


class publicInc
{
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

    public static function getCangzuRow($n){
        $row = [
            self::CHANGZU3=>true,
            self::CHANGZU6=>true,
            self::CHANGZU9=>true,
            self::CHANGZU12=>true,
        ];
        return $row[$n];
    }

    /**
     * 获取短租天数选项
     *
     * @return array
     */
    public static function getDuanzuList(){
        return [
            self::DUANZU3,
            self::DUANZU7,
            self::DUANZU15,
            self::DUANZU30,
        ];
    }

    public static function getDuanzuRow($n){
        $row = [
            self::DUANZU3=>true,
            self::DUANZU7=>true,
            self::DUANZU15=>true,
            self::DUANZU30=>true,
        ];
        return $row[$n];
    }

    /**
     * 根據周期返回相应天数
     * @param $zuqi
     * @return int
     */
    public static function calculateDay($zuqi){
        $day = 0;
        if($zuqi ==self::CHANGZU3){
            $day = 90;
        }else if($zuqi ==self::CHANGZU6){
            $day = 180;
        }else if($zuqi == self::CHANGZU9){
            $day = 270;
        }else{
            $day =365;
        }
        return $day;
    }
}