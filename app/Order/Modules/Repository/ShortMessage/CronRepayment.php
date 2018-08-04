<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderRepository;

/**
 * CronRepayment
 *
 * @author maxiaoyu
 */
class CronRepayment implements ShortMessage {

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
        // 查询分期信息
        $instalmentInfo = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['id'=>$this->business_no]);
        if( !is_array($instalmentInfo)){
            // 提交事务
            return false;
        }

        // 查询订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            return false;
        }

        // 用户信息
        $userInfo = \App\Lib\User\User::getUser($instalmentInfo['user_id']);
        if( !is_array($userInfo )){
            return false;
        }

        // 短息模板
        $code = $this->getCode($this->business_type);
        if( !$code ){
            return false;
        }

        $url = env('WEB_H5_URL') . 'myBillDetail?';

        $urlData = [
            'orderNo'       => $instalmentInfo['order_no'],     //  订单号
            'zuqi_type'     => $orderInfo['zuqi_type'],         //  租期类型
            'id'            => $instalmentInfo['id'],           //  分期ID
            'appid'         => $orderInfo['appid'],             //  商品编号
            'goodsNo'       => $instalmentInfo['goods_no'],     //  商品编号
        ];

        $zhifuLianjie = $url . createLinkstringUrlencode($urlData);

        // 短信参数
        $dataSms =[
            'realName'      => $userInfo['realname'],
            'zuJin'         => $instalmentInfo['amount'],
            'zhifuLianjie'  => createShortUrl($zhifuLianjie),
            'serviceTel'    => config('tripartite.Customer_Service_Phone'),
        ];

        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($userInfo['mobile'], $code, $dataSms);

    }

    // 支付宝内部消息通知
    public function alipay_notify(){
        return true;
    }
}
