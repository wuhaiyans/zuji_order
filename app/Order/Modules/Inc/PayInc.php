<?php
namespace App\Order\Modules\Inc;

class PayInc{
    /**
     * @var int 支付宝小程序支付
     */
    const  MiniAlipay = 5;
    /**
     * @var int 支付方式(银联支付)
     */
    const  UnionPay = 4;
    /**
     * @var int 押金预授权
     */
    const FlowerDepositPay = 3;
    /**
     * @var int 支付方式(花呗分期)
     */
    const FlowerStagePay = 2;
    /**
     * @var int 代扣
     */
    const WithhodingPay = 1;

    /**
     * 订单支付列表
     * @return array
     */
    public static function getPayList(){
        return [
            self::WithhodingPay => '代扣',
            self::FlowerStagePay => '花呗分期',
            self::FlowerDepositPay => '押金预授权',
            self::UnionPay => '银联支付',
            self::MiniAlipay=>'支付宝小程序支付',
        ];
    }

    /**
     * 订单支付方式
     * @param int $status 支付ID
     * @return string 支付名称
     */
    public static function getPayName($pay_type){
        $list = self::getPayList();
        if( isset($list[$pay_type]) ){
            return $list[$pay_type];
        }
        return '';
    }


}