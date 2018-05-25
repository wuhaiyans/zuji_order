<?php
/**
 * Created by PhpStorm.
 * User: limin
 * Date: 2018/5/24
 * Time: 10:26
 */
namespace App\Order\Modules\Inc;

class OrderBuyoutStatus{

    //--------------------------------------------------------------------------------------------
    //--+ 订单买断状态 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 初始状态
     */
    const OrderInitialize = 0;
    /**
     * @var int 已取消
     */
    const OrderCancel = 1;
    /**
     * @var int 已支付
     */
    const OrderPaid = 2;
    /**
     * @var int 已解押
     */
    const OrderRelease = 3;


    /**
     * 订单冻结类型
     * @return array
     */
    public static function getStatusType(){
        return [
            self::OrderInitialize => '待支付',
            self::OrderCancel => '已取消',
            self::OrderPaid => '已支付',
            self::OrderRelease => '已解押',
        ];
    }

    /**
     * 订单买断状态值 转换成 状态名称
     * @param int $status   订单买断状态值
     * @return string 订单买断状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

}

