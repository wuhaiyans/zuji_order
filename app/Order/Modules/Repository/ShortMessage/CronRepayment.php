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

        $App_url = env('APP_URL');

        $zhifuLianjie   = $App_url . "";

        // 短信参数
        $dataSms =[
            'realName'      => $userInfo['realname'],
            'zuJin'         => $instalmentInfo['amount'],
            'zhifuLianjie'  => $zhifuLianjie,
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
