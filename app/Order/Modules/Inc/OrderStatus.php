<?php
/**
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/4/27
 * Time: 11:26
 */
namespace App\Order\Modules\Inc;

class OrderStatus{

    //--------------------------------------------------------------------------------------------
    //--+ 订单状态 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 不可使用
     */
    const OrderInitialize = 0;
    /**
     * @var int 待支付【订单操作的起点】
     */
    const OrderWaitPaying = 1;
    /**
     * @var int 支付中
     */
    const OrderPaying =2;
    /**
     * @var int 已支付
     */
    const OrderPayed = 3;
    /**
     * @var int 备货中
     */
    const OrderInStock  = 4;
    /**
     * @var int 已发货
     */
    const OrderDeliveryed = 5;
    /**
     * @var int 租用中
     */
    const OrderInService = 6;
    /**
     * @var int 已取消完成(未支付)
     */
    const OrderCancel = 7;
    /**
     * @var int 关闭（支付完成后退款）
     */
    const OrderClosedRefunded = 8;
    /**
     * @var int 已完成（整个订单完成状态）
     */
    const OrderCompleted= 9;

    //未联系
    const visitUnContact = 0;
    //无法联系
    const visitNoContact = 1;
    //已联系
    const visitContacted = 2;
    //已回访
    const visited = 3;


    //--------------------------------------------------------------------------------------------
    //--+ 订单类型 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    //线上订单
    const orderOnlineService =1;
    //门店订单
    const orderStoreService =2;
    //小程序订单
    const orderMiniService=3;

    //--------------------------------------------------------------------------------------------
    //--+ 订单业务类型 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    //租机业务
    const BUSINESS_ZUJI =1;
    //退货业务
    const BUSINESS_RETURN=2;
    //换货业务
    const BUSINESS_BARTER =3;
	/**
	 * 还机业务
	 */
    const BUSINESS_GIVEBACK=4;
    //买断业务
    const BUSINESS_BUYOUT=5;
    //续租业务
    const BUSINESS_RELET =6;


    /**
     * 租期类型 1.天 2月
     */

    const ZUQI_TYPE_DAY = 1;
    const ZUQI_TYPE_MONTH = 2;


    /**
     * 订单租期类型
     * @return array
     */
    public static function getZuqiType(){
        return [
            self::ZUQI_TYPE_DAY => '天',
            self::ZUQI_TYPE_MONTH => '月',
        ];
    }
    /**
     * 订单租期类型 转换成 类型名称
     * @param int $status   订单租期类型值
     * @return string 订单租期类型名称
     */
    public static function getZuqiTypeName($status){
        $list = self::getZuqiType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


    /**
     * 订单租期类型
     * @return array
     */
    public static function getBusinessType(){
        return [
            self::BUSINESS_ZUJI => '租机业务',
            self::BUSINESS_RETURN => '退货业务',
            self::BUSINESS_BARTER => '换货业务',
            self::BUSINESS_GIVEBACK => '还机业务',
            self::BUSINESS_BUYOUT => '买断业务',
            self::BUSINESS_RELET => '续租业务',
        ];
    }

    /**
     * 订单核心业务类型 转换成 类型名称
     * @param int $status   订单类型值
     * @return string 订单类型名称
     */
    public static function getBusinessName($status){
        $list = self::getBusinessType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


    /**
     * 订单类型
     * @return array
     */
    public static function getOrderType(){
        return [
            self::orderOnlineService => '线上订单',
            self::orderStoreService => '门店订单',
            self::orderMiniService => '小程序订单',
        ];
    }

    /**
     * 订单类型值 转换成 类型名称
     * @param int $status   订单类型值
     * @return string 订单类型名称
     */
    public static function getTypeName($status){
        $list = self::getOrderType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

    /**
     * 订单状态
     * @return array
     */
    public static function getStatusType(){
        return [
            self::OrderWaitPaying => '待支付',
            self::OrderPaying => '支付中',
            self::OrderPayed => '已支付',
            self::OrderInStock => '备货中',
            self::OrderDeliveryed => '已发货',
            self::OrderInService => '租用中',
            self::OrderCancel => '已取消（未支付）',
            self::OrderClosedRefunded => '已关闭（已退款）',
            self::OrderCompleted => '已完成',

        ];
    }

    /**
     * 订单状态值 转换成 状态名称
     * @param int $status   订单状态值
     * @return string 订单状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }



    /**
     * 回访类型
     * @return array
     */
    public static function getVisitType(){
        return [
            self::visitUnContact => '未联系',
            self::visitNoContact => '无法联系',
            self::visitContacted => '已联系',
            self::visited => '已回访',
        ];
    }

    /**
     * 回访值 转换成 回访名称
     * @param int $status   回访值
     * @return string 回访名称
     */
    public static function getVisitName($status){
        $list = self::getVisitType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}

