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


}

