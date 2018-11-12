<?php
namespace App\Order\Modules\Repository\Zuji;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo,WithholdInfo,FundauthInfo};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use \App\Order\Modules\Inc\OrderGivebackStatus;
use \App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Service\OrderGoods;
use Mockery\Exception;

class Zuji implements BusinessPayInterface{

    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $business_no = '';
    private $status      = false;
    private $user_id     = 0;
    private $order       = null;
    private $pay_name    = '';

    public function __construct(string $business_no){

        $this->business_no = $business_no;
        $order = Order::getByNo($business_no);

        if($order){
            $orderInfo = $order->getData();
            $this->user_id = $orderInfo['user_id'];

            if($orderInfo['order_status'] == OrderStatus::OrderWaitPaying || $orderInfo['order_status'] == OrderStatus::OrderPaying){
                $this->status = true;
                $this->pay_name = "订单编号：".$this->business_no." 用户ID：".$this->user_id;

                $goods = OrderRepository::getGoodsListByOrderId($this->business_no);
                if(!$goods){
                   throw new Exception("订单商品信息不存在");
                }
                $zuqi = $goods[0]['zuqi'];
                $yajin = $orderInfo['order_yajin'];
                $zujin = $orderInfo['order_amount'] + $orderInfo['order_insurance'];
                $fenqi = $orderInfo['zuqi_type'] == OrderStatus::ZUQI_TYPE_DAY ? 0:$zuqi;

                $arr= $this->getOrderPayInfo([
                         'zujin' =>$zujin,       //【必须】商品租金 + 意外险
                         'yajin' =>$yajin,       //【必须】商品押金
                         'pay_type' =>$orderInfo['pay_type'],    //【必须】支付方式
                ]);

                //实例化支付方式并根据业务信息传值
                $this->pamentInfo = new PaymentInfo();
                $this->pamentInfo->setNeedPayment($arr['isPayment']);
                $this->pamentInfo->setPaymentAmount($arr['paymentAmount']);
                $this->pamentInfo->setPaymentFenqi($fenqi);
                $this->withholdInfo = new WithholdInfo();
                $this->withholdInfo->setNeedWithhold(false);
                $this->fundauthInfo = new FundauthInfo();
                $this->fundauthInfo->setNeedFundauth($arr['isFundauth']);

            }

        }

    }

    /**
     * 获取订单支付信息
     * @author wuhaiyan
     * @param $params
     * [
     *      'zujin' =>'',       //【必须】商品租金 + 意外险
     *      'yajin' =>'',       //【必须】商品押金
     *      'pay_type' =>'',    //【必须】支付方式
     * ]
     *
     * @return array
     * [
     *      'isFundauth'=>'',   // 是否需要预授权
     *      'isPayment'=>'',    // 是否需要一次性支付
     *      'paymentAmount'=>'',  // 需要支付金额
     * ]
     */

    public function getOrderPayInfo($params){
        $arr=[
            'isFundauth'=>false,
            'isPayment'=>false,
            'paymentAmount'=>0,
        ];

        $yajin = $params['yajin'];
        $zujin = $params['zujin'];
        $payType = $params['pay_type'];

        //判断分期

        //支付方式为代扣+预授权
        if($payType == PayInc::WithhodingPay){
            if($yajin > 0){$arr['isFundauth'] = true;}
            if($zujin > 0){$arr['isWithhold'] = true;}
        }

        //支付方式为 花呗支付 （一次性支付）
        if($payType == PayInc::FlowerStagePay || $payType == PayInc::UnionPay || $payType == PayInc::LebaifenPay || $payType == PayInc::LebaifenPay){
            if(($zujin+$yajin) >0){
                $arr['isPayment'] = true;
                $arr['paymentAmount'] = $zujin+$yajin;
            }
        }

        //花呗分期+预授权
        if($payType == PayInc::PcreditPayInstallment){
            if($yajin >0){$arr['isFundauth'] = true;}
            $arr['paymentAmount'] = $zujin;
        }

        return $arr;


    }

    /**
     *
     */
    public function getUserId(): int
    {
        return intval($this->user_id);
    }

    public function getPayName():string
    {

        return $this->pay_name;
    }

    public function getBusinessStatus(): bool
    {
        return !!$this->status;
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

}