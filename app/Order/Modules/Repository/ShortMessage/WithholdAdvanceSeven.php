<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;

/**
 * WithholdAdvanceSeven
 *
 * @author maxiaoyu
 */
class WithholdAdvanceSeven implements ShortMessage {

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
        /**
         * 当天发送短信设置  超时时间 12小时
         */
        $time = 60 * 60 * 12;
        if(redisIncr('WithholdAdvanceSeven'.$this->business_no, $time)>1) {
            LogApi::debug("[WithholdAdvanceSeven]短信已经发送");
            return false;
        }

        // 查询分期信息
        $instalmentInfo = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['id'=>$this->business_no]);
        if( !is_array($instalmentInfo)){
            LogApi::debug("扣款成功短信-分期详情错误",[$this->business_no]);
            return false;
        }

        \App\Lib\Common\LogApi::debug('[cronWithholdMessage:'.$instalmentInfo['order_no'].'提前7天扣款短信]');

        // 查询订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            LogApi::debug("扣款成功短信-订单详情错误",$instalmentInfo);
            return false;
        }

        // 用户信息
        $userInfo = \App\Lib\User\User::getUser($instalmentInfo['user_id']);
        if( !is_array($userInfo )){
            LogApi::debug("扣款成功短信-用户详情错误",$instalmentInfo);
            return false;
        }

        // 查询商品
        $orderGoods = New \App\Order\Modules\Service\OrderGoods();
        $goodsInfo  = $orderGoods->getGoodsInfo($instalmentInfo['goods_no']);
        if(!$goodsInfo){
            LogApi::debug("扣款成功短信-商品详情错误",$instalmentInfo);
            return false;
        }

        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        if( !$code ){
            return false;
        }

        $webUrl = env('WEB_H5_URL');
        $url = isset($webUrl) ? $webUrl : 'https://h5.nqyong.com/';
        $url = $url  . 'myBillDetail?';

        $urlData = [
            'orderNo'       => $instalmentInfo['order_no'],     //  订单号
            'zuqi_type'     => $orderInfo['zuqi_type'],         //  租期类型
            'id'            => $instalmentInfo['id'],           //  分期ID
            'appid'         => $orderInfo['appid'],             //  商品编号
            'goodsNo'       => $instalmentInfo['goods_no'],     //  商品编号
        ];

        $zhifuLianjie = $url . createLinkstringUrlencode($urlData);

        $createTime = $this->data;

        // 短信参数
        $dataSms =[
            'realName'      => $userInfo['realname'],
            'goodsName'     => $goodsInfo['goods_name'],
            'zuJin'         => $instalmentInfo['amount'],
            'createTime'    => $createTime['createTime'],
            'zhifuLianjie'  => createShortUrl($zhifuLianjie),
            'serviceTel'    => config('tripartite.Customer_Service_Phone'),
        ];

        \App\Lib\Common\LogApi::debug('[cronWithholdMessage:提前7天扣款]',$dataSms);

        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, $dataSms);

    }


    // 支付宝内部消息通知
    public function alipay_notify(){
        return true;
    }

}
