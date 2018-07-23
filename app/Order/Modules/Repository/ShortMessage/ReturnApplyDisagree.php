<?php

namespace App\Order\Modules\Repository\ShortMessage;
use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderReturnRepository;
use App\Order\Modules\Repository\Pay\Channel;

/**
 * OrderCreated
 *
 * @author Administrator
 */
class ReturnApplyDisagree implements ShortMessage {
	
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
	
	public function notify($data=[]){
		// 根据业务，获取短息需要的数据

        //获取退货单信息
        $return= \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($this->business_no);
        if( !$return ){
            return false;
        }
        $returnInfo=$return->getData();

        // 查询订单
        $order = \App\Order\Modules\Repository\Order\Order::getByNo($returnInfo['order_no']);
        if( !$order ){
            return false;
        }
        $orderInfo=$order->getData();

        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        LogApi::debug("退货不审核同意短信模板",$code);
        if( !$code ){
            return false;
        }
        //获取商品信息
        $goods=\App\Order\Modules\Repository\Order\Goods::getByGoodsNo($returnInfo['goods_no']);
        if(!$goods){
            return false;
        }
        $goodsInfo=$goods->getData();
        //获取用户认证信息
        $userInfo=OrderRepository::getUserCertified($returnInfo['order_no']);
        if(!$userInfo){
            return false;
        }
        return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName' => $userInfo['realname'],
            'orderNo' => $returnInfo['order_no'],
            'goodsName' => $goodsInfo['goods_name'],
            'serviceTel' => config('tripartite.Customer_Service_Phone'),
        ], $returnInfo['order_no']);

	}


	// 支付宝 短信通知
	public function alipay_notify(){
		return true;
	}
	
}
