<?php
namespace App\Order\Modules\Repository\Pay\BusinessPay;
/**
 * 代扣签约信息
 * @author gaobo
 */
class WithholdInfo{
    private $needWithhold   = false;
    private $withholdNo     = '';
    private $withholdStatus = 0;
    private $trade   = 'W';
    
    public function __construct()
    {
        
    }
    
    /**
     * 获取是否需要代扣
     * @return bool
     */
    public function getNeedWithhold(): bool{
        return $this->needWithhold;
    }
    
    /**
     * 获取代扣协议编号
     * @return string
     */
    public function getWithholdNo(): string{
        return $this->withholdNo;
    }
    
    /**
     * 获取代扣状态
     * @return int
     */
    public function getWithholdStatus(): int{
        return $this->withholdStatus;
    }
    
    /**
     * 设置别名
     * @return int
     */
    public function getTrate(): string{
        return $this->trade;
    }
    
    /**
     * 设置是否需要代扣
     * @param bool $needWithhold
     * @return bool
     */
    public function setNeedWithhold(bool $needWithhold): bool{
        return $this->needWithhold = $needWithhold;
    }
    
    /**
     * 设置代扣协议编号
     * @param string $withholdNo
     * @return bool
     */
    public function setWithholdNo(string $withholdNo): bool{
        return $this->withholdNo = $withholdNo;
    }
    
    /**
     * 设置代扣状态
     * @param int $withholdStatus
     * @return bool
     */
    public function setWithholdStatus(int $withholdStatus): bool{
        return $this->withholdStatus = $withholdStatus;
    }
}