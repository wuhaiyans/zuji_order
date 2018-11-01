<?php
namespace App\Order\Modules\Repository\Buyout;
use App\Order\Modules\Repository\Pay\BusinessPay\PaymentInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\WithholdInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\FundauthInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Service\OrderBuyout;

class Buyout implements BusinessPayInterface{
    
    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $did;
    
    public function __construct( int $bid, string $did ){
        //find
        $this->did = $did;
        $this->pamentInfo = new PaymentInfo();
        $this->pamentInfo->setNeedPayment(true);
        $this->withholdInfo = new WithholdInfo();
        $this->withholdInfo->setNeedWithhold(false);
        $this->fundauthInfo = new FundauthInfo();
        $this->fundauthInfo->setNeedFundauth(false);
    }
    
    /**
     * 获取业务信息
     * @param array $params
     */
    public function getBusinessInfo(string $did) : array
    {
        $buyout = OrderBuyout::getInfo($did);
        
        if(!$buyout){
            return apiResponse([],ApiStatus::CODE_50001,"没有找到该订单");
        }
        if($buyout['status']==OrderBuyoutStatus::OrderPaid){
            return apiResponse([],ApiStatus::CODE_0,"该订单已支付");
        }
        if($buyout['status']!=OrderBuyoutStatus::OrderInitialize){
            return apiResponse([],ApiStatus::CODE_0,"该订单支付异常");
        }
        $this->pamentInfo->setPaymentAmount($buyout['amount']);
        $this->pamentInfo->setPaymentFenqi(0);
        $this->pamentInfo->setPaymentNo(\creage_payment_no()); 
        
        return $buyout;
    }
    
    public function getPayByBusiness( int $business_type, string $business_no, int $lock=0 ) : array 
    {
        
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \App\Order\Modules\Repository\BusinessPay\BusinessPayInterface::getPaymentInfo()
     */
    public function getPaymentInfo(): PaymentInfo
    {
        return $this->pamentInfo;
    }
    
    /**
     * 代扣
     */
    public function getWithHoldInfo() : WithholdInfo
    {
        return $this->withholdInfo;
    }
    
    /**
     * 预授权
     */
    public function getFundauthInfo() : FundauthInfo
    {
        return $this->fundauthInfo;
    }
    
    /**
     * 添加日志
     */
    public function addLog()
    {
        //插入日志
        /* OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$buyout['order_no'],"用户买断发起支付","创建支付成功");
         //插入订单设备日志
         $log = [
         'order_no'=>$buyout['order_no'],
         'action'=>'用户买断支付',
         'business_key'=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT,//此处用常量
         'business_no'=>$buyout['buyout_no'],
         'goods_no'=>$buyout['goods_no'],
         'operator_id'=>$userInfo['uid'],
         'operator_name'=>$userInfo['username'],
         'operator_type'=>$userInfo['type'],
         'msg'=>'用户发起支付',
         ];
         GoodsLogRepository::add($log); */
    }
}