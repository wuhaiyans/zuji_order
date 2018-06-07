<?php

namespace App\Order\Modules\Inc;

class OrderFreezeStatus{

    //--------------------------------------------------------------------------------------------
    //--+ 订单冻结状态 --------------------------------------------------------------------------
    // ---冻结类型：0，不冻结；1，退款退货；2，还货；3，买断；4，换货；5，续租',
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 不冻结
     */
    const Non = 0;
    /**
     * @var int 退款
     */
    const GoodsReturn = 1;
    /**
     * @var int 还货
     */
    const Reback = 2;
    /**
     * @var int 买断
     */
    const Buyout  = 3;
    /**
     * @var int 换货
     */
    const Exchange = 4;
    /**
     * @var int 续租
     */
    const Relet = 5;


    //--------------------------------------------------------------------------------------------
    //--+ 订单状态 end ----------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * 订单状态列表
     * @return array    订单状态列表
     */
    public static function getStatusList(){
        return [
            self::Non => '不冻结',
            self::Refund => '退款退货',
            self::Reback => '还货',
            self::Buyout => '买断',
            self::Exchange => '换货',
            self::Relet => '续租',
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

