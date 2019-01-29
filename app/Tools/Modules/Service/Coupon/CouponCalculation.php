<?php
namespace App\Tools\Modules\Service\Coupon;

/**
 * Coupon 优惠券类型计算
 *
 * @author gaobo
 */

class CouponCalculation {
    private $amount       = 0;
    private $shop_price   = 0;
    private $limit        = 0;
    private $coupon_value = 0;
    
    public function __construct(int $amount , int $shop_price)
    {
        //初始化
        $this->amount = $amount; //月租金*租期+意外险;
        $this->shop_price = $shop_price;
    }
    
    //设置优惠券算法策略值
    public function setLimit(int $coupon_limit)
    {
        return $this->limit = $coupon_limit;
    }
    
    //设置优惠券算法策略值
    public function setCouponValue(int $coupon_value)
    {
        return $this->coupon_value = $coupon_value;
    }

    /**
     * 直免金额 立减
     */
    public function algorithm1():int
    {
        return $this->amount-$this->coupon_value;
    }
    
    /**
     * 租金折扣
     */
    public function algorithm2():int
    {
        return $this->amount*($this->coupon_value/100);
    }
    
    
    /**
     * 首月0租金
     */
    public function algorithm3():int
    {
        return $this->amount - $this->shop_price*100;
    }
    
    /**
     * 租金递减
     */
    public function algorithm4():int
    {
        return $this->amount - $this->coupon_value;
    }
    
    /**
     * 租金抵用
     */
    public function algorithm5():int
    {
        return $this->amount - $this->coupon_value;
    }
    
    /**
     * 满额减
     */
    public function algorithm6():int
    {
        //判断是否满额
        if($this->amount >= $this->limit){
            return $this->amount - $this->coupon_value;
        }else{
            return $this->amount;
        }
    }
    
    
    /**
     * 满量减
     */
    /* public function algorithm7():int
    {
        return $this->amount;
    } */
}
