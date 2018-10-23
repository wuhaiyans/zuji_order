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
     * 订单租期类型
     * @return array
     */
    public static function getBusinessType(){
        return [
            self::OrderWithhold     => '支付租金',
            self::OrderGiveback     => '支付还机赔偿金',
            self::OrderRefund       => '支付退货赔偿金',
            self::OrderBuyout       => '支付买断金',
            self::OrderRelet        => '支付续租金',
        ];
    }

}

