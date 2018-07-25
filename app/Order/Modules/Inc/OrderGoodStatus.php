<?php
/**
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/4/27
 * Time: 11:26
 */
namespace App\Order\Modules\Inc;

class OrderGoodStatus{

	//-+------------------------------------------------------------------------
	// |商品状态0：非启用；10： 租机中； 20：退货退款中 ，21 ：已退款； 30：  换货中， 31：已换货 ；40 ：还机中， 41：还机完成；50：买断中，51：买断完成； 60： 续租中， 61：续租完成
	//-+------------------------------------------------------------------------
    /**
	 * 非启用
     * @var int 0
     */
    const  INIT= 0;
    /**
	 * 租机中
     * @var int 1
     */
    const RENTING_MACHINE= 10;
    /**
	 * 退货中
     * @var int 2
     */
    const REFUNDS = 20;
    /**
	 * 已退货
     * @var int 3
     */
    const REFUNDED = 21;
    /**
	 * 换货中
     * @var int 4
     */
    const EXCHANGE_GOODS = 30;
    /**
	 * 已换货
     * @var int 5
     */
    const EXCHANGE_OF_GOODS =31;
    /**
	 * 还机中
     * @var int 6
     */
    const BACK_IN_THE_MACHINE =40;
    /**
	 * 还机完成
     * @var int 7
     */
    const COMPLETE_THE_MACHINE = 41;
    /**
	 * 还机关闭
     * @var int 7
     */
    const CLOSED_THE_MACHINE = 42;
    /**
     * 买断中
     * @var int 3
     */
    const BUY_OFF = 50;
    /**
     * 买断完成
     * @var int 4
     */
    const BUY_OUT = 51;
    /**
     * 续租中
     * @var int 5
     */
    const RELET = 60;
    /**
     * 续租完成
     * @var int 6
     */
    const RENEWAL_OF_RENT = 61;
    /**
     * 退款中
     */
    const REFUND = 70;

    /**
     * 已退款
     */
    const EXCHANGE_REFUND = 71;



    /**
     * 商品状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
            self::INIT                => '非启用',
            self::RENTING_MACHINE    => '租机中',
            self::REFUNDS             => '退货退款中',
            self::REFUNDED            => '已退货',
            self::EXCHANGE_GOODS      => '换货中',
            self::EXCHANGE_OF_GOODS    => '已换货',
            self::BACK_IN_THE_MACHINE  => '还机中',
            self::COMPLETE_THE_MACHINE => '已还机',
            self::BUY_OFF              => '买断中',
            self::BUY_OUT              => '买断完成',
            self::RELET                => '续租中',
            self::RENEWAL_OF_RENT     => '续租完成',
            self::EXCHANGE_REFUND     =>'已退款',
        ];
    }

    /**
     * 商品状态值 转换成 状态名称
     * @param int $status   商品状态值
     * @return string 商品状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

    /**
     * 获取成色值
     * @param $key
     * @return mixed
     */
    public static function spec_chengse_value($key){
        $chengse = array('100'=>'全新','99'=>'99成新','95'=>'95成新','90'=>'9成新','80'=>'8成新','70'=>'7成新',);
        return $chengse[$key];
    }

    /**
     * 获取出险类型状态
     * Author: heaven
     * @param $key
     * @return mixed
     */
    public static function getInsuranceTypeName($key){
        $info = array('1'=>'出险','2'=>'取消出险');
        return $info[$key];

    }



}

