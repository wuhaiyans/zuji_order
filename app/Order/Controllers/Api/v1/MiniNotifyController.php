<?php
/**
 *  芝麻小程序回调
 *   zhangjinhui
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;

use Illuminate\Support\Facades\Redis;
use App\Order\Modules\Service\OrderGiveback;
use App\Order\Modules\Inc\OrderGivebackStatus;

class MiniNotifyController extends Controller
{
    //取消
    private $CANCEL = 'ZM_RENT_ORDER_CANCEL';
    //完结
    private $FINISH = 'ZM_RENT_ORDER_FINISH';
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
            $result = \App\Order\Modules\Repository\OrderMiniNotifyLogRepository::add($_POST);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序取消订单回调记录失败',$_POST);
            }
            $this->orderCloseCancelNotify();
        } if($this->data['notify_type'] == $this->FINISH){
            //入库 完成 或 扣款 回调信息
            $result = \App\Order\Modules\Repository\OrderMiniNotifyLogRepository::add($_POST);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序完成 或 扣款 回调记录失败',$_POST);
            }
            $redis_order = Redis::get('dev:zuji:order:miniorder:orderno:'.$_POST['out_order_no']);
            if( $redis_order == 'MiniWithhold' ){
                $this->withholdingNotify();
                return;
            }else if( $redis_order == 'MiniOrderClose' ){
                $this->orderCloseCancelNotify();
                return;
            }
            \App\Lib\Common\LogApi::debug('小程序完成 或 扣款 回调处理错误',$_POST);
        }else if($this->data['notify_type'] == $this->CREATE){
            $result = \App\Order\Modules\Repository\OrderMiniRentNotifyRepository::add($_POST);
            if( !$result ){
                \App\Lib\Common\LogApi::debug('小程序订单确认支付回调记录失败',$_POST);
            }
            $this->rentTransition();
        }
    }

    /*
     * 芝麻支付宝小程序 订单关闭接口异步回调
     */
    private function orderCloseCancelNotify(){
        $b = \App\Order\Modules\Service\OrderCleaning::miniUnfreezeAndPayClean($this->data);
        if(!$b){
            echo "fail";return;
        }
        echo 'success';return;
    }

    /*
     * 芝麻支付宝小程序 订单扣款接口异步回调
     */
    private function  withholdingNotify(){
        $data = $this->data;
        //查询订单信息（获取用户id）
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['out_order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }
        // 扣款成功 修改分期状态
        if($data['pay_status'] == "PAY_SUCCESS"){
            //判断订单是否为还机冻结状态
            if($orderInfo['freeze_type'] == \App\Order\Modules\Inc\OrderFreezeStatus::Reback){
                //还机扣款操作
                $orderGivebackService = new OrderGiveback();
                //获取还机单基本信息
                $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($data['out_order_no']);
                //请求关闭订单接口
                if($orderGivebackInfo){

                }
            }else{
                $business_no = $data['out_trans_no'];
                $params = [
                    'status'=>'success',
                    'out_trade_no'=>$business_no,
                ];
                //修改分期状态
                $Instalment = new \App\Order\Modules\Repository\Order\Instalment();
                $Instalment->paySuccess($params);
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
        $params = [
            'business_type'=>1,
            'business_no'=>$data['out_order_no'],
            'status'=>'success',
        ];
        $b = \App\Order\Modules\Service\OrderPayNotify::callback($params);
        if (!$b) {
            echo "小程序订单支付失败";return;
        }
        echo 'success';return;
    }
}