<?php
namespace App\Order\Modules\Repository\Buyout;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo,WithholdInfo,FundauthInfo};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\GoodsLogRepository;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderBuyout;

class Buyout implements BusinessPayInterface{
    
    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $business_no = '';
    private $status      = false;
    private $user_id     = 0;
    private $butout      = null;
    private $pay_name    = '';
    
    public function __construct(string $business_no){
        //find
        $this->business_no = $business_no;
        $this->buyout = OrderBuyout::getInfo($business_no);
        if($this->buyout){
            $this->user_id = $this->buyout['user_id'];
            if($this->buyout['status'] == OrderBuyoutStatus::OrderInitialize){
                $this->status = true;
                $this->pay_name = '买断单号'.$this->buyout['buyout_no'].'订单编号'.$this->buyout['order_no'].'商品单号'.$this->buyout['goods_no'].'用户ID'.$this->butout['user_id'];
                
                $this->pamentInfo = new PaymentInfo();
                $this->pamentInfo->setNeedPayment(true);
                $this->pamentInfo->setPaymentAmount($this->buyout['amount']);
                $this->pamentInfo->setPaymentFenqi(0);
                $this->withholdInfo = new WithholdInfo();
                $this->withholdInfo->setNeedWithhold(false);
                $this->fundauthInfo = new FundauthInfo();
                $this->fundauthInfo->setNeedFundauth(false);
            }
        }
    }
    
    /**
     * 
     */
    public function getUserId()
    {
        return $this->user_id;
    }
    
    public function getPayName(){
        return $this->pay_name;
    }
    
    public function getBusinessStatus()
    {
        return $this->status;
    }
    
    /**
     * 获取业务信息
     * @param array $params
     */
    public function getBusinessInfo(string $business_no)
    {
        $buyout = OrderBuyout::getInfo($business_no);
        if($buyout){
            $this->pamentInfo->setPaymentAmount($buyout['amount']);
            $this->pamentInfo->setPaymentFenqi(0);
        }
        return $buyout;
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
    public function addLog(array $userInfo)
    {
        //插入日志
        OrderLogRepository::add($userInfo['uid'],$userInfo['username'],$userInfo['type'],$this->buyout['order_no'],"用户买断发起支付","创建支付成功");
         //插入订单设备日志
         $log = [
         'order_no'      => $this->buyout['order_no'],
         'action'        =>'用户买断支付',
         'business_key'  => OrderStatus::BUSINESS_BUYOUT,//此处用常量
         'business_no'   => $this->buyout['buyout_no'],
         'goods_no'      => $this->buyout['goods_no'],
         'operator_id'   => $this->user_id,
         'operator_name' => $userInfo['username'],
         'operator_type' => $userInfo['type'],
         'msg'=>'用户发起支付',
         ];
         GoodsLogRepository::add($log);
    }
}