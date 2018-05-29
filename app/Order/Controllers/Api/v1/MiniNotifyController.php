<?php
/**
 *  芝麻小程序回调
 *   zhangjinhui
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Order\Modules\Service\OrderInstalment;
use App\Order\Modules\Inc\OrderInstalmentStatus;


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
        $CommonMiniApi = new \App\Lib\AlipaySdk\sdk\CommonMiniApi( $appid );
        $b = $CommonMiniApi->verify( $_POST );
        if(!$b){
            \App\Lib\Common\LogApi::error('扣款回调验签','签名验证失败fail');
            echo '签名验证失败fail';exit;
        }
        $this->data = $_POST;
        if($this->data['notify_type'] == $this->CANCEL){
            //入库取消订单回调信息
            $result = \App\Order\Modules\Repository\MiniOrderNotifyLogRepository::add($_POST);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序取消订单回调记录失败',$result);
            }
            $this->OrderCancelNotify();
        } if($this->data['notify_type'] == $this->FINISH){
            //入库 完成 或 扣款 回调信息
            $result = \App\Order\Modules\Repository\MiniOrderNotifyLogRepository::add($_POST);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序完成 或 扣款 回调记录失败',$result);
            }
            $redis_order = Redis::get($_POST['out_order_no']);
            if( $redis_order == 'MiniWithhold' ){
                $this->withholdingNotify();
            }else if( $redis_order == 'MiniOrderClose' ){
                $this->orderCloseNotify();
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
        //查询订单信息（获取用户id）
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }
        $code = \App\Order\Modules\Service\OrderOperate::cancelOrder( $data['order_no'],$orderInfo['user_id'] );
        if( $code == ApiStatus::CODE_0 ){
            echo 'success';return;
        }
        echo $code.'取消订单错误';return;
    }

    /*
     * 芝麻支付宝小程序 订单关闭接口异步回调
     */
    private function orderCloseNotify(){
        $data = $this->data;
        //查询订单信息（获取用户id）
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }

        echo 'success';return;
    }

    /*
     * 芝麻支付宝小程序 订单扣款接口异步回调
     */
    private function  withholdingNotify(){
        $data = $this->data;
        //查询订单信息（获取用户id）
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }
        // 扣款成功 修改分期状态
        if($data['pay_status'] == "PAY_SUCCESS"){
            $trade_no = $data['out_trans_no'];
            //修改分期状态
            $b = OrderInstalment::save(['trade_no'=>$trade_no],['status'=>OrderInstalmentStatus::SUCCESS]);
            if(!$b){
                echo "修改分期状态.FAIL";exit;
            }
        }
        echo 'success';return;
    }

    /**
     * 确认订单异步通知接口
     *      订单创建成功异步通知
     */
    public function rentTransition(){
        $data = $this->data;
        //查询订单信息（获取用户id）
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }

        echo 'success';return;
    }
}