<?php
/**
 * 发货单常量状态
 * User: wangjinlin
 * Date: 2018/5/7
 * Time: 11:26
 */
namespace App\Warehouse\Modules\Inc;

class DeliveryStatus{

    //--------------------------------------------------------------------------------------------
    //--+ 发货单状态 --------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 无效
     */
    const DeliveryStatus0 = 0;
    /**
     * @var int 待配货
     */
    const DeliveryStatus1 = 1;
    /**
     * @var int 待发货
     */
    const DeliveryStatus2 = 2;
    /**
     * @var int 已发货
     */
    const DeliveryStatus3 = 3;
    /**
     * @var int 已签收完成
     */
    const DeliveryStatus4 = 4;
    /**
     * @var int 已拒签完成
     */
    const DeliveryStatus5 = 5;
    /**
     * @var int 已取消
     */
    const DeliveryStatus6 = 6;
    //--------------------------------------------------------------------------------------------
    //--+ 发货单状态 end ----------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * 发货单状态列表
     * @return array    订单状态列表
     */
    public static function getStatusList(){
        return [
            self::DeliveryStatus0 => '无效',
            self::DeliveryStatus1 => '待配货',
            self::DeliveryStatus2 => '待发货',
            self::DeliveryStatus3 => '已发货，待用户签收',
            self::DeliveryStatus4 => '已签收完成',
            self::DeliveryStatus5 => '已拒签完成',
            self::DeliveryStatus6 => '已取消',
        ];
    }

    /**
     * 发货状态值 转换成 状态名称
     * @param int $status   发货状态值
     * @return string 发货状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}

