<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Service\OrderGoodsInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;

/**
 * GivebackConfirmDelivery
 *
 * @author maxiaoyu
 */
class GivebackConfirmDelivery implements ShortMessage {

    private $business_type;
    private $business_no;

    public function setBusinessType( int $business_type ){
        $this->business_type = $business_type;
    }

    public function setBusinessNo( string $business_no ){
        $this->business_no = $business_no;
    }

    public function getCode($channel_id){
        $class =basename(str_replace('\\', '/', __CLASS__));
        return Config::getCode($channel_id, $class);
    }

    public function notify(){

        $orderGivebackService = new \App\Order\Modules\Service\OrderGiveback();
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($this->business_no);


        // 查询订单
        $orderInfo = OrderRepository::getInfoById($orderGivebackInfo['order_no']);
        if( !$orderInfo ){
            LogApi::debug("创建还机单-订单详情错误",$orderGivebackInfo);
            return false;
        }

        // 用户信息
        $userInfo = \App\Lib\User\User::getUser($orderGivebackInfo['user_id']);
        if( !is_array($userInfo )){
            return false;
        }

        //分期数据
        $instalmentList = OrderGoodsInstalment::queryList(['goods_no'=>$this->business_no,'status'=>[OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL]]);
        $amount = 0;
        if( !empty($instalmentList[$this->business_no]) ){
            foreach ($instalmentList[$this->business_no] as $instalmentInfo) {
                $amount += $instalmentInfo['amount'];
            }
        }

        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        if( !$code ){
            return false;
        }

        // 短信参数
        $dataSms =[
            'realName'          => $userInfo['realname'],
            'shengyuZujin'      => $amount,
        ];
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($userInfo['mobile'], $code, $dataSms);

    }

    // 支付宝内部消息通知
    public function alipay_notify(){
        return true;
    }
}
