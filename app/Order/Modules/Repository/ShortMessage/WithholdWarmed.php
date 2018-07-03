<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderRepository;

/**
 * WithholdWarmed
 *
 * @author maxiaoyu
 */
class WithholdWarmed implements ShortMessage {

    private $business_type;
    private $business_no;

    public function setBusinessType( int $business_type ){
        $this->business_type = $business_type;
    }

    public function setBusinessNo( string $business_no ){
        $this->business_no = $business_no;
    }

    public function getCode($channel_id){
        $class = basename(str_replace('\\', '/', __CLASS__));
        return Config::getCode($channel_id, $class);
    }

    public function notify(){
        // 根据业务，获取短息需要的数据

        // 查询分期信息
        $instalmentInfo = \APp\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['trade_no'=>$this->business_no]);
        if( !is_array($instalmentInfo)){
            // 提交事务
            return false;
        }

        // 查询订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            return false;
        }
        // 电话号
        $mobile = $orderInfo['mobile'];

        // 用户信息
        $userInfo = \App\Lib\User\User::getUser($instalmentInfo['user_id']);
        if( !is_array($userInfo )){
            return false;
        }

        // 查询商品
        $orderGoods = New \App\Order\Modules\Service\OrderGoods();
        $goodsInfo  = $orderGoods->getGoodsInfo($instalmentInfo['goods_no']);
        if(!$goodsInfo){
            return false;
        }

        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        if( !$code ){
            return false;
        }

        // 短信参数
        $dataSms = [
            'realName'      => $userInfo['realname'],
            'orderNo'       => $orderInfo['order_no'],
            'goodsName'     => $goodsInfo['goods_name'],
            'zuJin'         => $instalmentInfo['amount'],
            'serviceTel'    => env("CUSTOMER_SERVICE_PHONE"),
        ];

        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($mobile, $code, $dataSms);
    }

    // 支付宝 短信通知
    public function alipay_notify(){
        return true;
    }
}
