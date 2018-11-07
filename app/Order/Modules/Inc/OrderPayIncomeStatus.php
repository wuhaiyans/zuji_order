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
     * @var int 线下还款
     */
    const UNDERLINE = 4;

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
     * 入账渠道
     * @return array
     */
    public static function getBusinessType(){
        return [
            self::ORDERPAY 	    => '下单支付',
            self::WITHHOLD 	    => '分期代扣',
            self::REPAYMENT 	=> '主动还款',
            self::UNDERLINE 	=> '线下还款',
        ];
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
}

