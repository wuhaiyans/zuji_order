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
    /**
     * @var int 异常关闭（发生还机异常等）
     */
    const OrderAbnormal= 10;

    //未联系
    const visitUnContact = 0;
    //无法联系
    const visitNoContact = 1;
    //已联系
    const visitContacted = 2;
    //已回访
    const visited = 3;
    //有效订单
    const validOrder=20;


    //--------------------------------------------------------------------------------------------
    //--+ 订单类型 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    //线上订单
    const orderOnlineService =1;
    //门店订单
    const orderStoreService =2;
    //小程序订单
    const orderMiniService=3;
    //微回收
    const miniRecover=4;
    //活动领取订单
    const orderActivityService =5;

    //--------------------------------------------------------------------------------------------
    //--+ 订单业务类型 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    //租机业务
    const BUSINESS_ZUJI =1;
    //退货业务
    const BUSINESS_RETURN=2;
    //换货业务
    const BUSINESS_BARTER =3;
    //退款业务
    const BUSINESS_REFUND =8;
	/**
	 * 还机业务
	 */
    const BUSINESS_GIVEBACK=4;
    //买断业务
    const BUSINESS_BUYOUT=5;
    //续租业务
    const BUSINESS_RELET =6;
    //分期业务
    const BUSINESS_FENQI =7;
    //预定业务
    const BUSINESS_DESTINE =9;
    //体验活动业务
    const BUSINESS_EXPERIENCE =10;

    //--------------------------------------------------------------------------------------------
    //--+ 租期类型 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    //日租
    const ZUQI_TYPE1 = 1;
    //月租
    const ZUQI_TYPE2 = 2;


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
            self::BUSINESS_ZUJI         => '租机业务',
            self::BUSINESS_RETURN       => '退货业务',
            self::BUSINESS_BARTER       => '换货业务',
            self::BUSINESS_REFUND       => '退款业务',
            self::BUSINESS_GIVEBACK     => '还机业务',
            self::BUSINESS_BUYOUT       => '买断业务',
            self::BUSINESS_RELET        => '续租业务',
            self::BUSINESS_FENQI        => '分期业务',
            self::BUSINESS_DESTINE      => "预定业务",
            self::BUSINESS_EXPERIENCE   => "体验活动业务",
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
            self::miniRecover       => '微回收',
            self::orderActivityService => '活动领取订单',

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
            self::OrderWaitPaying => '已下单',
            self::OrderPaying => '支付中',
            self::OrderPayed => '已支付',
            self::OrderInStock => '备货中',
            self::OrderDeliveryed => '已发货',
            self::OrderInService => '租用中',
            self::OrderCancel => '已取消（未支付）',
            self::OrderClosedRefunded => '已关闭（已退款）',
            self::OrderCompleted => '已完成',
            self::OrderAbnormal => '异常关闭',
            self::validOrder => '有效订单',

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
     *  根据支付方式或者预定信息等获取订单类型
     * @param $params
     * [
     *      'pay_type' =>'',    //【可选】 int 支付方式
     *      'destine_no' =>'',  //【可选】 string 预定编号
     *      'appid_type'=>'',   //【必须】 int appid 类型
     *
     * ]
     *
     * @return int 订单状态
     */
    public static function getOrderTypeId($params){
        if(isset($params['pay_type']) && $params['pay_type'] == PayInc::LebaifenPay){
            //如果支付方式为乐百分 订单类型为 微回收
            $orderType =OrderStatus::miniRecover;
        }elseif($params['appid_type'] == AppIdInc::TYPE_H5){//H5
            $orderType =OrderStatus::orderOnlineService;
        }elseif($params['appid_type'] == AppIdInc::TYPE_API){//OPENAPI
            $orderType =OrderStatus::orderOnlineService;
        }elseif($params['appid_type'] == AppIdInc::TYPE_STORE){//线下门店
            $orderType =OrderStatus::orderStoreService;
            if(isset($params['destine_no']) && $params['destine_no']!=''){//如果有预订编号 则为领取订单
                $orderType =OrderStatus::orderActivityService;
            }
        }elseif($params['appid_type'] == AppIdInc::TYPE_ALI_ZHIMA){//支付宝小程序
            $orderType =OrderStatus::orderMiniService;
        }else{
            $orderType =OrderStatus::orderOnlineService;
        }
        return $orderType;
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
     * 
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


    /**
     *
     * 获取订单取消原因列表
     * Author: heaven
     * @return array
     *
     */
    public static function getOrderCancelResasonList(){
            return [
                '1'  => '额度不够',
                '2'  => '价格不划算',
                '3'  => '选错机型,重新下单',
                '4'  => '随便试试',
                '5'  => '不想租了',
                '6'  => '已经买了',
            ];

    }

    /**
     *
     * 获取订单取消原因名称
     * Author: heaven
     * @param $id
     * @return mixed|string
     */
    public static function getOrderCancelResasonName($id){
        $list = self::getOrderCancelResasonList();
        if( isset($list[$id]) ){
            return $list[$id];
        }
        return '';
    }



}

