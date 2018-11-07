<?php
namespace App\Order\Modules\Repository\Instalment;
use App\Order\Modules\Repository\Pay\BusinessPay\PaymentInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\WithholdInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\FundauthInfo;
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Service\OrderGoodsInstalment;


class Instalment implements BusinessPayInterface{

    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $business_no = '';
    private $status      = false;
    private $user_id     = 0;
    private $pay_name    = '';

    public function __construct(string $business_no){

        $this->business_no = $business_no;
        $instalmentInfo = OrderGoodsInstalment::getByBusinessNo($business_no);
        if($instalmentInfo){
            $this->user_id = $instalmentInfo['user_id'];
            // 判断支付状态
            if($instalmentInfo['status'] == OrderInstalmentStatus::UNPAID || $instalmentInfo['status'] == OrderInstalmentStatus::FAIL ){
                $this->status = true;
                $this->pay_name = '订单' .$instalmentInfo['order_no']. '分期'.$instalmentInfo['term'].'提前还款';

                //实例化支付方式并根据业务信息传值
                $this->pamentInfo = new PaymentInfo();
                $this->pamentInfo->setNeedPayment(true);
                $this->pamentInfo->setPaymentAmount($instalmentInfo['amount']);
                $this->pamentInfo->setPaymentFenqi(0);
                $this->withholdInfo = new WithholdInfo();
                $this->withholdInfo->setNeedWithhold(false);
                $this->fundauthInfo = new FundauthInfo();
                $this->fundauthInfo->setNeedFundauth(false);
            }
        }
    }

    /**
     * 获取用户ID
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * 获取支付名称
     */
    public function getPayName(){
        return $this->pay_name;
    }

    /**
     * 获取支付交易状态
     */
    public function getBusinessStatus(){
        return $this->status;
    }

    /**
     * 获取支付信息
     * @see \App\Order\Modules\Repository\BusinessPay\BusinessPayInterface::getPaymentInfo()
     */
    public function getPaymentInfo(): PaymentInfo
    {
        return $this->pamentInfo;
    }

    /**
     * 签约代扣信息
     */
    public function getWithHoldInfo() : WithholdInfo
    {
        return $this->withholdInfo;
    }

    /**
     * 签约预授权信息
     */
    public function getFundauthInfo() : FundauthInfo
    {
        return $this->fundauthInfo;
    }

}