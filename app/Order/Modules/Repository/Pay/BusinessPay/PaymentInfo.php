<?php
namespace App\Order\Modules\Repository\Pay\BusinessPay;

/**
 * 直付信息
 * @author gaobo
 */
class PaymentInfo{
    private $needPayment    = true;
    private $isLeBaiFen     = true;
    private $paymentAmount  = 0.00;
    private $paymentNo      = '';
    private $paymentFenqi   = 0;
    private $trade          = 'P';
    
    public function __construct()
    {
        
    }
    
    /**
     * 是否需要直付
     * @return bool
     */
    public function getNeedPayment(): bool{
        return $this->needPayment;
    }
    
    /**
     * 获取直付金额
     * @return float
     */
    public function getPaymentAmount(): float{
        return $this->paymentAmount;
    }
    
    /**
     * 获取直付渠道
     * @return int
     */
    public function getPaymentNo(): string{
        return $this->paymentNo;
    }
    
    /**
     * 获取分期
     * @return int
     */
    public function getPaymentFenqi(): int{
        return $this->paymentFenqi;
    }
    
    /**
     * 设置别名
     * @return int
     */
    public function getTrate(): string{
        return $this->trade;
    }
    
    /**
     * 设置是否需要直付
     * @return bool
     */
    public function setNeedPayment(bool $needPyment): bool{
        return $this->needPayment = $needPyment;
    }
    
    /**
     * 设置直付金额
     * @return float
     */
    public function setPaymentAmount(float $paymentAmount): float{
        return $this->paymentAmount = $paymentAmount;
    }
    
    /**
     * 设置直付渠道
     * @return int
     */
    public function setPaymentNo(string $paymentNo): string{
        return $this->paymentNo = $paymentNo;
    }
    
    /**
     * 设置分期
     * @return int
     */
    public function setPaymentFenqi(int $paymentFenqi): int{
        return $this->paymentFenqi = $paymentFenqi;
    }
}