<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;


class UnderPayStatus{

    /**
     * @var int 支付租金
     */
    const OrderWithhold = 7;
    /**
     * @var int 支付还机赔偿金
     */
    const OrderGiveback = 4;
    /**
     * @var int 支付退货赔偿金
     */
    const OrderRefund  = 2;
    /**
     * @var int 支付买断金
     */
    const OrderBuyout = 5;
    /**
     * @var int 支付续租金
     */
    const OrderRelet  = 6;




    /**
     * @var int 线下转账方式 银行转账
     */
    const UNDER_BANK = 1;
    /**
     * @var int 线下转账方式 支付宝
     */
    const UNDER_ALIPAY = 2;
    /**
     * @var int 线下转账方式 微信
     */
    const UNDER_WECHAT = 3;




    /**
     * 订单租期类型
     * @return array
     */
    public static function getBusinessType(){
        return [
            self::OrderWithhold     => '支付租金',
            self::OrderGiveback     => '支付赔偿金',
//            self::OrderRefund       => '支付退货赔偿金',
            self::OrderBuyout       => '支付买断金',
            self::OrderRelet        => '支付续租金',
        ];
    }

    /**
     * 订单租期类型 名称
     * @return array
     */
    public static function getBusinessTypeName(int $type):string {
        $list = self::getBusinessType();
        if( isset($list[$type]) ){
            return $list[$type];
        }
        return '';
    }

    /**
     * 订单租期类型 实例化类名称
     * @return array
     */
    public static function getBusinessClassName(int $type):string {
        $list =  [
            self::OrderWithhold     => 'OrderWithhold',
            self::OrderGiveback     => 'OrderGiveback',
            self::OrderRefund       => 'OrderRefund',
            self::OrderBuyout       => 'OrderBuyout',
            self::OrderRelet        => 'OrderRelet',
        ];
        if( isset($list[$type]) ){
            return $list[$type];
        }
        return '';
    }

    /**
     * 线下缴款类型
     * @return array
     */
    public static function getUnderBusinessType(){
        return [
            self::UNDER_BANK 	    => '银行转账',
            self::UNDER_ALIPAY 	    => '支付宝转账',
            self::UNDER_WECHAT 	    => '微信转账',
        ];
    }

    /**
     * 获取线下支付方式方式名称
     * @param  $type int 线下入账方式类型
     * @return string 线下入账方式名称
     */
    public static function getUnderLineBusinessTypeName(int $type):string {
        $list = self::getUnderBusinessType();
        return $list[$type];
    }
}

