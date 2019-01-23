<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;

/**
 * GivebackReturnDeposit
 *
 * @author maxiaoyu
 */
class GivebackReturnDeposit implements ShortMessage {

    private $business_type;
    private $business_no;
    private $data;

    public function setBusinessType( int $business_type ){
        $this->business_type = $business_type;
    }

    public function setBusinessNo( string $business_no ){
        $this->business_no = $business_no;
    }

    public function setData( array $data ){
        $this->data = $data;
    }

    public function getCode($channel_id){
        $class =basename(str_replace('\\', '/', __CLASS__));
        return Config::getCode($channel_id, $class);
    }

    public function notify(){

        // 查询商品
        $orderGoods = New \App\Order\Modules\Service\OrderGoods();
        $goodsInfo  = $orderGoods->getGoodsInfo($this->business_no);
        if(!$goodsInfo){
            LogApi::debug("退还押金-商品详情错误",$goodsInfo);
            return false;
        }

        // 查询订单
        $orderInfo = OrderRepository::getInfoById($goodsInfo['order_no']);
        if( !$orderInfo ){
            LogApi::debug("退还押金-订单详情错误",$goodsInfo);
            return false;
        }

        // 用户信息
        $userInfo = \App\Lib\User\User::getUser($goodsInfo['user_id']);
        if( !is_array($userInfo )){
            return false;
        }

        $backObj = new \App\Order\Modules\Repository\OrderGivebackRepository();
        $backInfo = $backObj->getInfoByGoodsNo($this->business_no);
        if( !$backInfo ){
            return false;
        }

        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        if( !$code ){
            return false;
        }

        $lianjie = "https://h5.nqyong.com/index?appid=" . $orderInfo['appid'];

        /** 短信参数
         * 尊敬的{realName}您好，您租赁的{goodsName}，订单号：{orderNo}，押金{tuihuanYajin}已退还！您可以登录 {lianjie} 继续租用其他好物。感谢您对拿趣用的支持！
         */
        $tuihuanYajin = $goodsInfo['surplus_yajin'] - $backInfo['compensate_amount'] > 0 ? bcsub($goodsInfo['surplus_yajin'], $backInfo['compensate_amount'], 2) : 0;
        $dataSms =[
            'realName'          => $userInfo['realname'],
            'goodsName'         => $goodsInfo['goods_name'],
            'orderNo'           => $orderInfo['order_no'],
            'tuihuanYajin'      => $tuihuanYajin,
            'lianjie'           => createShortUrl($lianjie),
        ];

        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, $dataSms);

    }

    // 支付宝内部消息通知
    public function alipay_notify(){
        return true;
    }
}
