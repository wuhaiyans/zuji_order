<?php
/**
 *  芝麻小程序回调
 *   zhangjinhui
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;


class MiniNotifyController extends Controller
{
    //取消
    private $CANCEL = 'ZM_RENT_ORDER_CANCEL';
    //完结
    private $FINISH = 'ZM_RENT_ORDER_FINISH';
//    //分期扣款
//    private $INSTALLMENT = 'ZM_RENT_ORDER_INSTALLMENT';
    //确认订单
    private $CREATE = 'ZM_RENT_ORDER_CREATE';
    //返回数组
    private $data = [];

    /*
     * 芝麻支付宝小程序 代扣接口(订单关闭 订单取消)异步回调
     */
    public function withholdingCloseCancelNotify(){
        if( ! isset($_POST['notify_app_id']) ){
            \App\Lib\Common\LogApi::error('芝麻小程序回调参数错误',$_POST);
            echo '芝麻小程序回调参数错误';exit;
        }
        $appid = $_POST['notify_app_id'];
        $CommonMiniApi = new \App\Lib\Payment\mini\sdk\CommonMiniApi( $appid );
        $b = $CommonMiniApi->verify( $_POST );
        if(!$b){
            \App\Lib\Common\LogApi::error('扣款回调验签','签名验证失败fail');
            echo '签名验证失败fail';exit;
        }
        $this->data = $_POST;
        if($this->data['notify_type'] == $this->CANCEL){
            $this->OrderCancelNotify();
        } if($this->data['notify_type'] == $this->FINISH){
            $this->orderCloseNotify();
            if( isset($redis_order) ){
                $this->withholdingNotify();
                echo 'success';
                exit;
            }
        }else if($this->data['notify_type'] == $this->CREATE){
            $this->rentTransition();
        }
    }

    /*
     * 芝麻支付宝小程序 订单取消接口异步回调
     */
    private function OrderCancelNotify(){
        $data = $this->data;
        $code = \App\Order\Modules\Service\OrderOperate::cancelOrder( $data['order_no'] );
        echo '取消订单成功';
    }

    /*
     * 芝麻支付宝小程序 订单关闭接口异步回调
     */
    private function orderCloseNotify(){
        $data = $this->data;
        echo '取消订单成功';
    }

    /*
     * 芝麻支付宝小程序 订单扣款接口异步回调
     */
    private function  withholdingNotify(){
        $data = $this->data;

        echo '取消订单成功';
    }

    /**
     * 确认订单异步通知接口
     *      订单创建成功异步通知
     */
    public function rentTransition(){
        $orders = $this->data;
        //获取appid
        $appid =$orders['appid'];
        $pay_type = \App\Order\Modules\PayInc::IncMiniAlipay;//支付方式ID
        $address_id=$orders['params']['address_id'];//收货地址ID
        $sku =$orders['params']['sku_info'];
        $coupon = $orders['params']['coupon'];
        $user_id = 18;

        //判断参数是否设置
        if(empty($pay_type)){
            echo '支付方式不能为空';die;
        }
        if(empty($appid)){
            echo 'appid不能为空';die;
        }
        if(empty($address_id)){
            echo '收货地址不能为空';die;
        }
        if(count($sku)<1){
            echo '商品ID不能为空';die;
        }

        $data =[
            'appid'=>1,
            'pay_type'=>1,
            'address_id'=>8,
            'sku'=>$sku,
            'coupon'=>["b997c91a2cec7918","b997c91a2cec7000"],
            'user_id'=>18,  //增加用户ID
        ];
        $OrderOperate = new \App\Order\Modules\Service\OrderCreater();
        $res = $OrderOperate->create($data);
        if(!$res){
            echo '确认订单失败';die;
        }
        echo '确认订单成功';die;
    }
}