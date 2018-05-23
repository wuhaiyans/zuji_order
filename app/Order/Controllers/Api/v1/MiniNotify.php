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

    }

    /*
     * 芝麻支付宝小程序 订单关闭接口异步回调
     */
    private function orderCloseNotify(){

    }

    /*
     * 芝麻支付宝小程序 订单扣款接口异步回调
     */
    private function  withholdingNotify(){

    }

    /**
     * 确认订单异步通知接口
     *      订单创建成功异步通知
     */
    public function rentTransition(){

    }
}