<?php
namespace App\Order\Modules\Repository\Pay\BusinessPay;

/**
 * 直付信息
 * @author gaobo
 */
class FundauthInfo{
    private $needFundauth   = false;
    private $fundauthAmount = 0.00;
    private $fundauthNo     = '';
    private $fundauthStatus = 0;
    private $trade   = 'F';
    
    public function __construct()
    {
        
    }
    
    /**
     * 是否需要预授权
     * @return bool
     */
    public function getNeedFundauth(): bool{
        return $this->needFundauth;
    }
    
    /**
     * 获取预授金额
     * @return float
     */
    public function getFundauthAmount(): float{
        return $this->fundauthAmount;
    }
    
    /**
     * 获取预授权码
     * @return string
     */
    public function getFundauthNo(): string{
        return $this->fundauthNo;
    }
    
    /**
     * 获取业务链中预授权状态
     * @return int
     */
    public function getFundauthStatus(): int{
        return $this->fundauthStatus;
    }
    
    /**
     * 设置别名
     * @return int
     */
    public function getTrate(): string{
        return $this->trade;
    }
    
    /**
     * 设置是否需要预授权
     * @param bool $needFundauth
     * @return bool
     */
    public function setNeedFundauth(bool $needFundauth): bool{
        return $this->needFundauth = $needFundauth;
    }
    
    /**
     * 设置预授金额
     * @param unknown $fundauthAmount
     * @return bool
     */
    public function setFundauthAmount($fundauthAmount): bool{
        return $this->fundauthAmount = $fundauthAmount;
    }
    
    /**
     * 设置预授权码
     * @param unknown $fundauthNo
     * @return bool
     */
    public function setFundauthNo($fundauthNo): bool{
        return $this->fundauthNo = $fundauthNo;
    }
    
    /**
     * 设置业务链中预授权状态
     * @param unknown $fundauthStatus
     * @return bool
     */
    public function setFundauthStatus($fundauthStatus): bool{
        return $this->fundauthStatus = $fundauthStatus;
    }
}