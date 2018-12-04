<?php

namespace App\Order\Modules\Repository\ShortMessage;
use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;

/**
 * ReturnApply
 * @author maxiaoyu
 */
class ReletSuccess implements ShortMessage {

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

        LogApi::debug("[ReletMessage]短息参数",[
            'business_no'   => $this->business_no
        ]);
        //获取续租对象
        $reletObj = \App\Order\Modules\Repository\Relet\Relet::getByReletNo($this->business_no);
        $relet = $reletObj->getData();
        if( !$relet ){
            LogApi::debug("[ReletMessage]短信获取续租单信息错误".$this->business_no);
            return false;
        }

        // 查询订单
        $order = \App\Order\Modules\Repository\Order\Order::getByNo($relet['order_no']);
        $orderInfo = $order->getData();
        if( !$orderInfo ){
            LogApi::debug("[ReletMessage]短信获取订单信息错误".$this->business_no);
            return false;
        }


        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        if( !$code ){
            LogApi::debug("[ReletMessage]短息模板",$code);
            return false;
        }

        //获取商品信息
        $goods=\App\Order\Modules\Repository\Order\Goods::getByGoodsId($relet['goods_id']);
        $goodsInfo=$goods->getData();
        if(!$goodsInfo){
            LogApi::debug("[ReletMessage]短信获取商品信息",$goodsInfo);
            return false;
        }


        //获取用户认证信息
        $userInfo = OrderRepository::getUserCertified($orderInfo['order_no']);
        if(!$userInfo){
            LogApi::debug("[ReletMessage]短信获取用户认证信息",$userInfo);
            return false;
        }

        $endTime = date('Y-m-d',$goodsInfo['end_time']);

        // 尊敬的用户{realName}您好，您租赁的{goodsName}，将继续租赁{days}，到期时间为{endTime}，感谢您对拿趣用的认可！如需帮助，可致电客服：{serviceTel}。
        $dataSms = [
            'realName'      => $userInfo['realname'],
            'goodsName'     => $goodsInfo['goods_name'],
            'days'          => $relet['zuqi'],
            'endTime'       => $endTime,
            'serviceTel'    => config('tripartite.Customer_Service_Phone'),
        ];
        LogApi::debug("[ReletMessage]短信参数",$dataSms);
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, $dataSms);
    }

    // 支付宝 短信通知
    public function alipay_notify(){
        return true;
    }

}
