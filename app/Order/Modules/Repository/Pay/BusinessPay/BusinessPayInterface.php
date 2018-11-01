<?php
namespace App\Order\Modules\Repository\Pay\BusinessPay;
use App\Order\Modules\Repository\Pay\BusinessPay\PaymentInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\WithholdInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\FundauthInfo;
/**
 * 业务接口,每种业务需要实现以下三个接口
 * @author gaobo
 * @access public
 * @copyright (c) 2017, Huishoubao
 */
interface BusinessPayInterface{
    
    /**
     * 获取业务信息
     * @param array $params
     */
    public function getBusinessInfo(string $did) : array;
    
    /**
     * 直付
     */
    public function getPaymentInfo() : PaymentInfo;
    
    /**
     * 代扣
     */
    public function getWithHoldInfo() : WithholdInfo;
    
    /**
     * 预授权
     */
    public function getFundauthInfo() : FundauthInfo;
    
    /**
     * 写日志
     */
    public function addLog();
    
}