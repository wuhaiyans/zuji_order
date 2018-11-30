<?php
/**
 * 分期组件创建构造器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Common\LogApi;
use App\Order\Controllers\Api\v1\InstalmentController;
use App\Order\Models\Order;
use App\Order\Models\OrderGoodsInstalment;
use App\Order\Modules\Inc\CouponStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Repository\Pay\WithholdQuery;
use App\Order\Modules\Service\OrderInstalment;
use Mockery\Exception;

class OrderPayComponnet implements OrderCreater
{
    //组件
    private $componnet;

    private $payType;
    private $userId;
    private $payChannelId;

    //订单编号
    private $orderNo;
    //订单押金
    private $orderYajin;
    //订单租金
    private $orderZujin;
    //订单分期
    private $orderFenqi;

    //是否需要支付
    private $isPay=true;
    //是否要一次性支付
    private $paymentStatus=false;
    //是否需要签约代扣
    private $withholdStatus=false;
    //是否需要资金预授权
    private $fundauthStatus=false;
    //判断是否已经签约代扣
    private $isWithholdStatus=false;


    public function __construct(OrderCreater $componnet,int $userId)
    {
        $this->componnet = $componnet;
        $this->payType = $this->componnet->getOrderCreater()->getSkuComponnet()->getPayType();
        $this->userId = $userId;
        $this->payChannelId = PayInc::getPayChannelName($this->payType);
    }
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this->componnet->getOrderCreater();
    }
    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
    public function filter(): bool
    {
        $filter =  $this->componnet->filter();
        $this->orderNo = $this->getOrderCreater()->getOrderNo();
        $this->orderYajin =$this->getOrderCreater()->getSkuComponnet()->getOrderYajin();
        $this->orderZujin =$this->getOrderCreater()->getSkuComponnet()->getOrderZujin();
        $this->orderFenqi =$this->getOrderCreater()->getSkuComponnet()->getOrderFenqi();

        // 租金押金 都为0 不需要支付
        if($this->orderZujin ==0 && $this->orderYajin==0){
            $this->isPay =false;
        }
        //-+--------------------------------------------------------------------
        // | 判断租金支付方式（分期/代扣）
        //-+--------------------------------------------------------------------
        //代扣方式支付租金
        if( $this->payType == PayInc::WithhodingPay ){
            //判断是否已经签约代扣
            $this->isWithholdStatus = $this->isWithholdQuery($this->userId,$this->payChannelId);
            if(!$this->isWithholdStatus){
                $this->withholdStatus =true;
            }
            //判断 如果押金大于0 需要预授权
            if($this->orderYajin>0){
                $this->fundauthStatus =true;
            }
        }
        //一次性方式支付租金
        elseif( $this->payType == PayInc::FlowerStagePay || $this->payType == PayInc::UnionPay || $this->payType == PayInc::LebaifenPay || $this->payType == PayInc::PcreditPayInstallment || $this->payType == PayInc::WeChatPay){
            if($this->orderZujin>0){
                $this->paymentStatus =true;
            }
            if($this->orderYajin >0){
                $this->fundauthStatus =true;
            }
        }
        //花呗预授权支付租金 押金
        elseif($this->payType == PayInc::FlowerFundauth){
            if($this->orderZujin + $this->orderYajin >0){
                $this->fundauthStatus =true;
            }
        }

        return $filter;
    }

    /**
     *  是否签约代扣查询
     * @param
     * $userId 用户ID
     * $payChannelId 支付渠道
     * @return boolean
     */
    private function isWithholdQuery($userId,$payChannelId):bool {
        try{
            $withhold = WithholdQuery::getByUserChannel($userId,$payChannelId);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema =$this->componnet->getDataSchema();
        $pay['pay_info'] =[
            'withholdStatus' => $this->withholdStatus,
            'paymentStatus' => $this->paymentStatus,
            'fundauthStatus' =>$this->fundauthStatus,
            'isPay' =>$this->isPay,
        ];
        return array_merge($schema,$pay);
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $schema =$this->getDataSchema();
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }

        //判断是否需要支付 如果需要支付则更新订单状态
        if(!$this->isPay){
            $data['order_status']=OrderStatus::OrderPayed;
            $data['pay_time']= time();
            $b =Order::where('order_no', '=', $this->orderNo)->update($data);
            if(!$b){
                LogApi::alert("OrderCreate:更新订单已支付状态失败",$data,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."OrderCreate-Update-OrderStatus-error",$data);
                $this->getOrderCreater()->setError("OrderCreate-Update-OrderStatus-error");
                return false;
            }
            //不需要支付则不生成支付单，退出
            return true;
        }

        $orderInsurance =$this->getOrderCreater()->getSkuComponnet()->getOrderInsurance();
        $zuqiType = $this->getOrderCreater()->getSkuComponnet()->getZuqiType();
        if($zuqiType ==1){
            $this->orderFenqi =0;
        }
        $param =[
            'payType' => $this->payType,//支付方式 【必须】<br/>
            'payChannelId' => $this->payChannelId,//支付渠道 【必须】<br/>
            'userId' => $this->userId,//业务用户ID 【必须】<br/>
            'businessType' => OrderStatus::BUSINESS_ZUJI,//业务类型（租机业务 ）【必须】<br/>
            'businessNo' => $this->orderNo,//业务编号（订单编号）【必须】<br/>
            'orderNo' => $this->orderNo,//业务编号（订单编号）【必须】<br/>
            'fundauthAmount' =>$this->orderYajin,//Price 预授权金额（押金），单位：元【必须】<br/>
            'paymentAmount' => $this->orderZujin,//Price 支付金额（总租金），单位：元【必须】<br/>  包含意外险
            'paymentFenqi' => $this->orderFenqi,//int 分期数，取值范围[0,3,6,12]，0：不分期【必须】<br/>
            'yiwaixian' =>$orderInsurance,//Price 订单的意外险金额 单位：元 【必须】
        ];
        try{
            //代扣方式支付租金
            if( $this->payType == PayInc::WithhodingPay ) {
                //判断是否需要绑定订单
                if ($this->isWithholdStatus) {
                    $withhold = WithholdQuery::getByUserChannel($this->userId, $this->payChannelId);
                    $b = $withhold->bind([
                        'business_type' => $param['businessType'],  // 【必须】int    业务类型
                        'business_no' => $param['businessNo'],  // 【必须】string  业务编码
                    ]);
                    if (!$b) {
                        LogApi::alert("OrderCreate:绑定代扣协议失败",[
                            'business_type' => $param['businessType'],  // 【必须】int    业务类型
                            'business_no' => $param['businessNo'],  // 【必须】string  业务编码
                        ],[config('web.order_warning_user')]);
                        LogApi::error(config('app.env')."OrderCreate-Blind-WithholdStatus-error：".$this->userId,[
                            'business_type' => $param['businessType'],  // 【必须】int    业务类型
                            'business_no' => $param['businessNo'],  // 【必须】string  业务编码
                        ]);

                        $this->getOrderCreater()->setError('OrderCreate-Blind-WithholdStatus-error');
                        return false;
                    }
                }
                //需要签约代扣+预授权金额为0 【创建签约代扣的支付单】
                if($this->withholdStatus && $param['fundauthAmount']==0){
                    \App\Order\Modules\Repository\Pay\PayCreater::createWithhold($param);
                }
                //需要签约代扣+预授权金额不为0 【创建签约代扣+预授权的支付单】
                elseif($this->withholdStatus && $param['fundauthAmount']!=0){
                    \App\Order\Modules\Repository\Pay\PayCreater::createWithholdFundauth($param);
                }
                //不需要签约代扣+预授权金额不为0 【创建预授权支付单】
                elseif(!$this->withholdStatus && $param['fundauthAmount']!=0){
                    \App\Order\Modules\Repository\Pay\PayCreater::createFundauth($param);
                }
                //花呗 , 银联支付 ，花呗分期+预授权
            }elseif( $this->payType == PayInc::FlowerStagePay || $this->payType == PayInc::UnionPay || $this->payType == PayInc::PcreditPayInstallment){
                if($param['fundauthAmount'] == 0){
                    //预授权金额为0  创建普通支付
                    \App\Order\Modules\Repository\Pay\PayCreater::createPayment($param);
                }else{
                    //预授权金额不为0 【创建预授权支付单】
                    \App\Order\Modules\Repository\Pay\PayCreater::createPaymentFundauth($param);
                }
            }
            // 乐百分支付方式 一次性普通支付单
            elseif($this->payType == PayInc::LebaifenPay){
                \App\Order\Modules\Repository\Pay\PayCreater::createLebaifenPayment($param);
            }
            //微信支付 为 一次性支付
            elseif ($this->payType == PayInc::WeChatPay){
                $param['paymentAmount'] = $this->orderZujin + $this->orderYajin;
                //创建普通支付
                \App\Order\Modules\Repository\Pay\PayCreater::createPayment($param);
            }
            //花呗预授权支付订单租金 和押金
            elseif ($this->payType == PayInc::FlowerFundauth){
                $param['fundauthAmount'] = $this->orderZujin + $this->orderYajin;
                \App\Order\Modules\Repository\Pay\PayCreater::createFundauth($param);
            }


        }catch (Exception $e){
            LogApi::alert("OrderCreate:增加支付单失败",['error'=>$e->getMessage()],[config('web.order_warning_user')]);
            LogApi::error("OrderCreate-Add-OrderPay-error:".$e->getMessage());
            $this->getOrderCreater()->setError($e->getMessage());
            return false;
        }

        return true;

    }
}