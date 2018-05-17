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
     * @var int 已支付
     */
    const OrderPayed = 2;
    /**
     * @var int 备货中
     */
    const OrderInStock  = 3;
    /**
     * @var int 已发货
     */
    const OrderDeliveryed = 4;
    /**
     * @var int 租用中
     */
    const OrderInService = 5;
    /**
     * @var int 关闭:已取消完成
     */
    const OrderClosed = 6;
    /**
     * @var int 退货退款完成单
     */
    const OrderRefunded = 7;
    /**
     * @var int 还机完成单
     */
    const OrderGivebacked= 8;
    /**
     * @var int 买断完成单
     */
    const OrderBuyouted = 9;
    /**
     * @var int 换货完成单
     */
    const OrderChanged= 10;

    //未联系
    const visitUnContact = 0;
    //无法联系
    const visitNoContact = 1;
    //已联系
    const visitContacted = 2;
    //已回访
    const visited = 3;


    /**
     * 订单冻结类型
     * @return array
     */
    public static function getStatusType(){
        return [
            self::OrderWaitPaying => '待支付',
            self::OrderPayed => '已支付',
            self::OrderInStock => '备货中',
            self::OrderDeliveryed => '已发货',
            self::OrderInService => '租用中',
            self::OrderClosed => '关闭',
            self::OrderRefunded => '退货退款完成单',
            self::OrderGivebacked => '还机完成单',
            self::OrderBuyouted => '买断完成单',
            self::OrderChanged => '换货完成单',
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

