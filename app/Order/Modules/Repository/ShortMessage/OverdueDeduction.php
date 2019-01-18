<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderOverdueDeductionRepository;

/**
 * OverdueDeduction
 *
 * @author maxiaoyu
 */
class OverdueDeduction implements ShortMessage {

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


        $overdueDeductionInfo = OrderOverdueDeductionRepository::info(['business_no' => $this->business_no]);
        if( !is_array($overdueDeductionInfo)){
            LogApi::error('[OverdueDeduction]逾期扣除押金短信-数据错误');
            return false;
        }

        // 查询订单
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($overdueDeductionInfo['order_no']);
        if( !$orderInfo ){
            LogApi::debug("[OverdueDeduction]逾期扣除押金短信-订单详情错误",$overdueDeductionInfo);
            return false;
        }

        // 用户信息
        $userInfo = \App\Lib\User\User::getUser($orderInfo['user_id']);
        if( !is_array($userInfo )){
            LogApi::debug("[OverdueDeduction]逾期扣除押金短信-用户详情错误",$overdueDeductionInfo);
            return false;
        }

        // 查询商品
        $goodsInfo = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$overdueDeductionInfo['order_no']]);
        if(!$goodsInfo){
            LogApi::debug("[OverdueDeduction]逾期扣除押金短信-商品详情错误",$orderInfo);
            return false;
        }

        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        if( !$code ){
            LogApi::debug("[OverdueDeduction]逾期扣除押金短信-模板错误",$code);
            return false;
        }

        // 短信参数
        $dataSms =[
            'realName'      => $userInfo['realname'],
            'goodsName'     => $goodsInfo['goods_name'],
            'Amont'         => $overdueDeductionInfo['deduction_amount'],
        ];
        \App\Lib\Common\LogApi::debug('[OverdueDeduction]逾期扣除押金短信',$dataSms);
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, $dataSms);

    }


    // 支付宝内部消息通知
    public function alipay_notify(){
        return true;
    }

}
