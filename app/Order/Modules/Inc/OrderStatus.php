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
    //--------------------------------------------------------------------------------------------
    //--+ 订单状态 end ----------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * 订单状态列表
     * @return array    订单状态列表
     */
    public static function getStatusList(){
        return [
            self::OrderWaitPaying => '待支付',
        ];
    }

    /**
     * 订单状态值 转换成 状态名称
     * @param int $status   订单状态值
     * @return string 订单状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}

