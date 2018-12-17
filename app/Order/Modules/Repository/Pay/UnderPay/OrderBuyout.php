<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\GoodsLogRepository;
use App\Order\Modules\Repository\Order\Goods;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Repository\OrderBuyoutRepository;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ShortMessage\BuyoutPayment;
use App\Order\Modules\Repository\ShortMessage\Config;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use App\Order\Modules\Service\OrderOperate;
use Illuminate\Support\Facades\DB;

class OrderBuyout implements UnderLine {


    /**
     * 商品编号
     */
    protected $order_no = '';

    private $componnet = null;


    public function __construct( $params ) {
        $this->order_no = $params['order_no'];

        $this->componnet = $params;
    }



    /**
     * 计算该付款金额
     * return string
     */
    public function getPayAmount(){
        $where = [
            ['order_no','=',$this->order_no],
            ['status','=',OrderBuyoutStatus::OrderInitialize],
        ];
        $buyoutInfo = OrderBuyoutRepository::getInfo($where);
        if($buyoutInfo){
            return $buyoutInfo['amount'];
        }
        $goodsInfo = Goods::getOrderNo($this->order_no);
        $goodsInfo = $goodsInfo->getData();
        if($goodsInfo){
            return $goodsInfo['buyout_price'];
        }
        return false;
    }

    /**
     * 实现具体业务
     * @return bool true  false
     */
    public function execute(){
        $amount = $this->componnet['amount'];
        //获取订单信息
        $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$this->order_no));
        if(!$orderInfo){
            throw new \Exception("订单信息未查到");
        }
        if($orderInfo['order_status'] == OrderStatus::OrderCompleted){
            LogApi::info("offline-buyout","该订单已完成");
            throw new \Exception("该订单已完成");
        }
        //获取订单商品信息;
        $goodsInfo = Goods::getOrderNo($this->order_no);
        if(!$goodsInfo){
            throw new \Exception("获取订单商品信息失败");
        }
        $goodsInfo = $goodsInfo->getData();
        $where = [
            ['order_no','=',$this->order_no],
        ];
        //获取买断单
        $buyout = OrderBuyoutRepository::getInfo($where);
        if($buyout['status'] == OrderBuyoutStatus::OrderPaid || $buyout['status'] == OrderBuyoutStatus::OrderRelease){
            LogApi::info("offline-buyout","该订单已经买断支付");
            throw new \Exception("该订单已经买断支付");
        }
        if(!$buyout){
            $buyout = [
                'type'=>1,
                'buyout_no'=>createNo(8),
                'order_no'=>$goodsInfo['order_no'],
                'goods_no'=>$goodsInfo['goods_no'],
                'user_id'=>$goodsInfo['user_id'],
                'plat_id'=>$goodsInfo['user_id'],
                'goods_name'=>$goodsInfo['goods_name'],
                'buyout_price'=>$amount,
                'amount'=>$amount,
                'create_time'=>time()
            ];

        }
        //关闭分期
        $data = [
            'order_no'=>$this->order_no,
            'goods_no'=>$goodsInfo['goods_no'],
        ];
        if($orderInfo['pay_type'] == PayInc::FlowerFundauth){
            $ret = OrderGoodsInstalmentRepository::UnFinishWithhold($buyout['order_no']);
            if(!$ret){
                LogApi::alert("buyout-callback:扣除分期租金失败",$data,self::$email);
                return false;
            }
        }
        $ret = Instalment::close($data);
        if(!$ret){
            LogApi::info("offline-buyout","关闭分期失败");
            throw new \Exception("关闭分期失败");
        }

        //清算处理数据拼接
        $clearData = [
            'order_type'=> $orderInfo['order_type'],
            'order_no' => $buyout['order_no'],
            'business_type' => ''.OrderStatus::BUSINESS_BUYOUT,
            'business_no' => $buyout['buyout_no']
        ];
        if($goodsInfo['yajin']>0 ){
            $clearData['auth_unfreeze_amount'] = $goodsInfo['yajin'];
            $clearData['auth_unfreeze_status'] = OrderCleaningStatus::depositUnfreezeStatusUnpayed;
            $clearData['status'] = OrderCleaningStatus::orderCleaningUnfreeze;
            if($orderInfo['order_type'] != OrderStatus::orderMiniService){
                $payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_ZUJI,$orderInfo['order_no'] );
                $clearData['out_auth_no'] = $payObj->getFundauthNo();
                $clearData['out_payment_no'] = $payObj->getPaymentNo();
            }
            //进入清算处理
            $orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
            if(!$orderCleanResult){
                //预警通知 参数1：问题标记  参数2：程序相关错误数据  参数3：通知人邮箱
                LogApi::alert("offline-buyout:买断进入清算失败",$clearData,["limin@huishoubao.com"]);
                //错误信息日志记录
                LogApi::info("offline-buyout:进入清算失败",$clearData);
                throw new \Exception("进入清算失败");
            }
        }


        //设置短信发送内容
        $smsContent = [
            'mobile'=>$orderInfo['mobile'],
            'realName'=>$orderInfo['realname'],
            'buyoutPrice'=>normalizeNum($buyout['amount'])."元",
        ];
        //相应支付渠道使用相应短信模板
        if($orderInfo['channel_id'] == Config::CHANNELID_MICRO_RECOVERY){
            $smsContent['lianjie'] = createShortUrl('https://h5.nqyong.com/index?appid=' . $orderInfo['appid']);
        }
        $smsCode = SceneConfig::BUYOUT_PAYMENT;
        //发送短信
        BuyoutPayment::notify($orderInfo['channel_id'],$smsCode,$smsContent);
        //日志记录
        $orderLog = [
            'uid'=>0,
            'username'=>$orderInfo['realname'],
            'type'=>\App\Lib\PublicInc::Type_System,
            'order_no'=>$orderInfo['order_no'],
            'title'=>"线下买断支付成功",
            'msg'=>"线下支付完成",
        ];
        $goodsLog = [
            'order_no'=>$buyout['order_no'],
            'action'=>'用户买断支付',
            'business_key'=> OrderStatus::BUSINESS_BUYOUT,//此处用常量
            'business_no'=>$buyout['buyout_no'],
            'goods_no'=>$buyout['goods_no'],
            'msg'=>'线下买断支付成功',
        ];
        self::log($orderLog,$goodsLog);

        $buyout['status'] = OrderBuyoutStatus::OrderPaid;
        //不需要解冻则直接完成订单
        if($goodsInfo['yajin']==0 ){
            $buyout['status'] = OrderBuyoutStatus::OrderRelease;
            //如果订单冻结就解冻订单
            if($orderInfo['freeze_type']>0){
                $ret = OrderRepository::orderFreezeUpdate($goodsInfo['order_no'],OrderFreezeStatus::Non);
                if(!$ret){
                    LogApi::info("offline-buyout","解冻订单失败");
                    throw new \Exception("解冻订单失败");
                }
            }

            //更新订单商品
            $goods = [
                'goods_status' => \App\Order\Modules\Inc\OrderGoodStatus::BUY_OUT,
                'business_no' => $buyout['buyout_no'],
            ];
            $OrderGoodsRepository = new OrderGoodsRepository();
            $ret = $OrderGoodsRepository->update(['id'=>$goodsInfo['id']],$goods);
            if(!$ret){
                LogApi::info("offline-buyout","更新商品失败");
                throw new \Exception("更新商品失败");
            }

            $ret = OrderOperate::isOrderComplete($buyout['order_no']);
            if(!$ret){
                LogApi::info("offline-buyout","关闭订单失败");
                throw new \Exception("关闭订单失败");
            }

            //设置短信发送内容
            $smsContent = [
                'mobile'=>$orderInfo['mobile'],
                'realName'=>$orderInfo['realname'],
                'buyoutPrice'=>normalizeNum($buyout['amount'])."元",
            ];
            //相应支付渠道使用相应短信模板
            if($orderInfo['channel_id'] == Config::CHANNELID_MICRO_RECOVERY){
                $smsContent['lianjie'] =  createShortUrl('https://h5.nqyong.com/index?appid=' . $orderInfo['appid']);
            }
            $smsCode = SceneConfig::BUYOUT_PAYMENT_END;
            //发送短信
            BuyoutPayment::notify($orderInfo['channel_id'],$smsCode,$smsContent);
            //日志记录
            $orderLog = [
                'uid'=>0,
                'username'=>$orderInfo['realname'],
                'type'=>\App\Lib\PublicInc::Type_System,
                'order_no'=>$orderInfo['order_no'],
                'title'=>"线下买断完成",
                'msg'=>"无押金直接买断完成",
            ];
            $goodsLog = [
                'order_no'=>$buyout['order_no'],
                'action'=>'线下用户买断完成',
                'business_key'=> OrderStatus::BUSINESS_BUYOUT,//此处用常量
                'business_no'=>$buyout['buyout_no'],
                'goods_no'=>$buyout['goods_no'],
                'msg'=>'线下买断完成',
            ];
            self::log($orderLog,$goodsLog);
        }

        $ret = OrderRepository::orderFreezeUpdate($buyout['order_no'],OrderFreezeStatus::Buyout);
        if(!$ret){
            LogApi::info("offline-buyout","线下买断冻结订单失败");
            throw new \Exception("线下买断冻结订单失败");
        }

        if(isset($buyout['id'])){
            //更新买断单
            $ret = OrderBuyoutRepository::setOrderPaid($buyout['id']);
        }
        else{
            //创建买断单
            $ret = OrderBuyoutRepository::create($buyout);
        }

        if(!$ret){
            LogApi::info("offline-buyout","更新商品失败");
            throw new \Exception("更新商品失败");
        }
        return true;
    }
    static function log($orderLog,$goodsLog){
        //插入日志
        OrderLogRepository::add($orderLog['uid'],$orderLog['username'],$orderLog['type'],$orderLog['order_no'],$orderLog['title'],$orderLog['msg']);
        //插入订单设备日志
        GoodsLogRepository::add($goodsLog);
    }
}
