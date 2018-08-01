<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Order\Modules\Repository\OrderRepository;

/**
 * Repayment
 *
 * @author maxiaoyu
 */
class Repayment implements ShortMessage {

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

        // 短信参数
        $dataSms =[
            'realName'      => $userInfo['realname'],
        ];
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($userInfo['mobile'], $code, $dataSms);

    }

    // 支付宝内部消息通知
    public function alipay_notify(){
        try{
            // 查询分期信息
            $instalmentInfo = \App\Order\Modules\Service\OrderGoodsInstalment::queryInfo(['id'=>$this->business_no]);
            if( !is_array($instalmentInfo)){
                // 提交事务
                return false;
            }

            $alipayUserId = \App\Lib\User\User::getUserAlipayId($instalmentInfo['user_id']);
            if( empty($alipayUserId)){
                return true;
            }

            //通过用户id查询支付宝用户id
            $MessageSingleSendWord = new \App\Lib\AlipaySdk\sdk\MessageSingleSendWord($alipayUserId);
            //查询账单
            $year = substr($instalmentInfo['term'], 0, 4);
            $month = substr($instalmentInfo['term'], -2);
            $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
            $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
            $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
            $messageArr = [
                'amount' => $instalmentInfo['amount'],
                'bill_type' => '租金',
                'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
                'pay_time' => date('Y-m-d H:i:s'),
            ];
            $b = $MessageSingleSendWord->PaySuccess($messageArr);
            if ($b === false) {
                \App\Lib\Common\LogApi::error("发送消息通知错误-");
            }
            return true;

        }catch (\Exception $exc) {
            \App\Lib\Common\LogApi::error("发送支付宝内部消息通知错误");
            return true;
        }
    }
}
