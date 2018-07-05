<?php
namespace App\Order\Modules\Inc;

class OrderPayIncomeStatus{

    /**
     * @var int 下单支付
     */
    const ORDERPAY = 1;
    /**
     * @var int 代扣
     */
    const WITHHOLD = 2;
    /**
     * @var int 主动还款
     */
    const REPAYMENT = 3;


    /**
     * 入账渠道
     * @return array
     */
    public static function getBusinessType(){
        return [
            self::ORDERPAY 	    => '下单支付',
            self::WITHHOLD 	    => '分期代扣',
            self::REPAYMENT 	=> '主动还款',
        ];
    }
}

