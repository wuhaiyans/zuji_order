<?php
namespace App\Order\Modules\Repository\Pay\BusinessPay;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo , WithholdInfo , FundauthInfo};
/**
 * 业务接口,每种业务需要实现以下八个接口
 * @author gaobo
 * @access public
 * @copyright (c) 2017, Huishoubao
 */
interface BusinessPayInterface{
    
    /**
     * 获取用户ID
     * @return int
     */
    public function getUserId() : int;
    
    /**
     * 获取直付名称
     * @return string
     */
    public function getPayName() : string;
    
    /**
     * 获取业务状态
     * @return bool 可支付为true 不可支付为false
     */
    public function getBusinessStatus() : bool;
    
    /**
     * 获取直付信息
     * @return PaymentInfo
     */
    public function getPaymentInfo() : PaymentInfo;
    
    /**
     * 获取代扣签约信息
     * @return WithholdInfo
     */
    public function getWithHoldInfo() : WithholdInfo;
    
    /**
     * 获取预授权信息
     * @return FundauthInfo
     */
    public function getFundauthInfo() : FundauthInfo;
    
}