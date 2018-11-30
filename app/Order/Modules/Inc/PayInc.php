<?php
namespace App\Order\Modules\Inc;

use App\Order\Modules\Repository\Pay\Channel;

class PayInc{
    /**
     * @var int 花呗预授权支付
     */
    const FlowerFundauth = 10;
    /**
     * @var int 线下支付
     */
    const UnderLinePay = 9;
    /**
     * @var int 花呗分期+预授权
     */
    const  PcreditPayInstallment = 8;

    /**
     * @var int 微信支付
     */
    const  WeChatPay = 7;
    /**
     * @var int 乐百分支付
     */
    const  LebaifenPay = 6;

    /**
     * @var int 支付宝小程序支付
     */
    const  MiniAlipay = 5;
    /**
     * @var int 支付方式(银联支付)
     */
    const  UnionPay = 4;
    /**
     * @var int 押金预授权/作废
     */
    const FlowerDepositPay = 3;
    /**
//     * @var int 支付方式(花呗分期)  租金+意外险   押金
     */
    const FlowerStagePay = 2;
    /**
     * @var int 代扣+预授权
     */
    const WithhodingPay = 1;



    /**
     * 订单支付列表
     * @return array
     */
    public static function getPayList(){
        return [
            self::WithhodingPay => '代扣+预授权',
            self::FlowerStagePay => '支付宝支付',
            self::FlowerDepositPay => '押金预授权/作废',
            self::UnionPay => '银联支付',
            self::MiniAlipay=>'支付宝小程序支付',
            self::LebaifenPay=>'乐百分支付',
            self::WeChatPay=>'微信支付',
            self::PcreditPayInstallment=>'花呗分期+预授权',
            self::UnderLinePay=>'线下支付',
            self::FlowerFundauth=>'花呗预授权',
        ];
    }

    /**
     * 支付方式获取支付渠道列表
     * @return array
     */
    public static function getPayChannelList(){
        return [
            self::WithhodingPay => Channel::Alipay,
            self::FlowerStagePay => Channel::Alipay,
            self::FlowerDepositPay => Channel::Alipay,
            self::UnionPay => Channel::Unionpay,
            self::MiniAlipay=>Channel::Alipay,
            self::LebaifenPay=>Channel::Lebaifen,
            self::WeChatPay=>Channel::Wechat,
            self::PcreditPayInstallment=>Channel::Alipay,
            self::FlowerFundauth=>Channel::Alipay,
        ];
    }

    /**
     * 支付方式获取支付渠道
     * @param int $status 支付ID
     * @return string 支付名称
     */
    public static function getPayChannelName($pay_type){
        $list = self::getPayChannelList();
        if( isset($list[$pay_type]) ){
            return $list[$pay_type];
        }
        return '';
    }

    /**
     * 预约支付列表
     * @return array
     */
    public static function getOppointmentPayList(){
        return [
            self::FlowerStagePay => '支付宝支付',
            self::WeChatPay=>'微信支付',
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