<?php
namespace App\Order\Modules\Repository\Pay\UnderPay;

use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\GoodsLogRepository;
use App\Order\Modules\Repository\Order\Goods;
use App\Order\Modules\Repository\Order\Instalment;
use App\Order\Modules\Repository\OrderBuyoutRepository;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderRepository;

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
        $goodsInfo = Goods::getByOrderNo($this->order_no);
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
            return false;
        }
        //获取订单商品信息;
        $goodsInfo = Goods::getByOrderNo($this->order_no);
        if(!$goodsInfo){
            return false;
        }
        $where = [
            ['order_no','=',$this->order_no],
        ];
        //获取买断单
        $buyout = OrderBuyoutRepository::getInfo($where);
        //如果存在买断单并且支付禁止执行
        if(in_array($buyout['status'],[OrderBuyoutStatus::OrderPaid,OrderBuyoutStatus::OrderRelease])){
            return false;
        }
        $data = [
            'order_no'=>$this->order_no,
            'goods_no'=>$goodsInfo['goods_no'],
        ];
        $ret = Instalment::close($data);
        if(!$ret){
            return false;
        }
        //更新买断单或创建买断单
        if($buyout){
            $ret = OrderBuyoutRepository::setOrderPaid($buyout['id']);
        }else{
            //创建买断单
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
                'status'=>OrderBuyoutStatus::OrderPaid,
                'create_time'=>time()
            ];
            $ret = OrderBuyoutRepository::create($data);
        }
        if(!$ret){
            return false;
        }


        //清算处理数据拼接
        $clearData = [
            'order_type'=> $orderInfo['order_type'],
            'order_no' => $buyout['order_no'],
            'business_type' => ''.OrderStatus::BUSINESS_BUYOUT,
            'business_no' => $buyout['buyout_no']
        ];
        $payObj = null;
        if($goodsInfo['yajin']>0 ){

            $payObj = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_ZUJI,$orderInfo['order_no'] );
            $clearData['out_auth_no'] = $payObj->getFundauthNo();
            $clearData['auth_unfreeze_amount'] = $goodsInfo['yajin'];
            $clearData['auth_unfreeze_status'] = OrderCleaningStatus::depositUnfreezeStatusUnpayed;
            $clearData['status'] = OrderCleaningStatus::orderCleaningUnfreeze;

            if($orderInfo['order_type'] == OrderStatus::orderMiniService){
                $clearData['auth_unfreeze_amount'] = $goodsInfo['yajin'];
                $clearData['auth_unfreeze_status'] = OrderCleaningStatus::depositUnfreezeStatusUnpayed;
                $clearData['status'] = OrderCleaningStatus::orderCleaningUnfreeze;
            }
            elseif($orderInfo['order_type'] == OrderStatus::miniRecover){
                $clearData['out_payment_no'] = $payObj->getPaymentNo();
            }
            \App\Lib\Common\LogApi::info( '出账详情', ['obj'=>$payObj,"no"=>$payObj->getPaymentNo()] );
        }


        //进入清算处理
        $orderCleanResult = \App\Order\Modules\Service\OrderCleaning::createOrderClean($clearData);
        if(!$orderCleanResult){
            return false;
        }
        if($goodsInfo['yajin']==0){
            $params = [
                'business_type'     => $clearData['business_type'],
                'business_no'     => $clearData['business_no'],
                'status'     => 'success',//支付状态
            ];

            $result = self::callbackOver($params,[]);
            if(!$result){
                return false;
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
                'title'=>"买断完成",
                'msg'=>"无押金直接买断完成",
            ];
            $goodsLog = [
                'order_no'=>$buyout['order_no'],
                'action'=>'用户买断完成',
                'business_key'=> OrderStatus::BUSINESS_BUYOUT,//此处用常量
                'business_no'=>$buyout['buyout_no'],
                'goods_no'=>$buyout['goods_no'],
                'msg'=>'买断完成',
            ];
            self::log($orderLog,$goodsLog);
            return true;
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
            'title'=>"买断支付成功",
            'msg'=>"支付完成",
        ];
        $goodsLog = [
            'order_no'=>$buyout['order_no'],
            'action'=>'用户买断支付',
            'business_key'=> OrderStatus::BUSINESS_BUYOUT,//此处用常量
            'business_no'=>$buyout['buyout_no'],
            'goods_no'=>$buyout['goods_no'],
            'msg'=>'买断支付成功',
        ];
        self::log($orderLog,$goodsLog);

        return true;

    }
    static function log($orderLog,$goodsLog){
        //插入日志
        OrderLogRepository::add($orderLog['uid'],$orderLog['username'],$orderLog['type'],$orderLog['order_no'],$orderLog['title'],$orderLog['msg']);
        //插入订单设备日志
        GoodsLogRepository::add($goodsLog);
    }
}
