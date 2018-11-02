<?php
namespace App\Order\Modules\Repository\Pay\BusinessPay;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo , WithholdInfo , FundauthInfo};
/**
 * 业务接口,每种业务需要实现以下五个接口
 * @author gaobo
 * @access public
 * @copyright (c) 2017, Huishoubao
 */
interface BusinessPayInterface{
    
    /**
     * 获取业务信息
     * @param array $params
     */
    public function getBusinessInfo(string $business_no) : array;
    
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
    public function addLog(array $business_info) : array;
    
}