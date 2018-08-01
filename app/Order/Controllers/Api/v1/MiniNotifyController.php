<?php
/**
 *  芝麻小程序回调
 *   zhangjinhui
 *   date:2018-05-14
 */
namespace App\Order\Controllers\Api\v1;

use Illuminate\Support\Facades\Redis;
use App\Order\Modules\Service\OrderGiveback;
use App\Lib\ApiStatus;
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

    /**
     * 芝麻支付宝小程序 代扣接口(订单关闭 订单取消)异步回调
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    public function withholdingCloseCancelNotify(){
        \App\Lib\Common\LogApi::notify('芝麻小程序回调参数记录',$_POST);
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
        try{
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
        }catch(\Exception $ex){
            //记录日志
            \App\Lib\Common\LogApi::debug('小程序处理异常',$ex->getMessage());
            echo $ex->getMessage();exit;
        }

    }

    /**
     * 芝麻支付宝小程序 订单关闭接口异步回调
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    private function orderCloseCancelNotify(){
        //判断订单是否为还机关闭订单


        //开启事务
        \DB::beginTransaction();
        $b = \App\Order\Modules\Service\OrderCleaning::miniUnfreezeAndPayClean($this->data);
        if(!$b){
            //事物回滚 记录日志
            \DB::rollBack();
            \App\Lib\Common\LogApi::debug('订单关闭处理失败',$this->data);
            echo "fail";return;
        }
        \DB::commit();
        echo 'success';return;
    }

    /**
     * 芝麻支付宝小程序 订单扣款接口异步回调
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    private function  withholdingNotify(){
        $data = $this->data;
        //查询订单信息
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById( $data['out_order_no'] );
        if( $orderInfo == false ){
            echo '订单不存在';return;
        }
        // 扣款成功 修改分期状态
        if($data['pay_status'] == "PAY_SUCCESS"){
            //开启事务
            \DB::beginTransaction();
            //判断订单是否为还机冻结状态
            if($orderInfo['freeze_type'] == \App\Order\Modules\Inc\OrderFreezeStatus::Reback){
                //还机扣款操作
                $orderGivebackService = new OrderGiveback();
                //获取当前订单下商品
                $orderGoods = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsByOrderNo($data['out_order_no']);
                if(!$orderGoods){
                    //记录日志
                    \App\Lib\Common\LogApi::debug('当前订单查询商品不存在',$orderGoods);
                }
                $orderGoods = $orderGoods->toArray();
                //获取还机单基本信息
                $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($orderGoods[0]['goods_no']);
                //请求关闭订单接口
                if($orderGivebackInfo){
                    //修改租金支付成功状态
                    $orderGivebackResult = $orderGivebackService->update(['goods_no'=>$orderGivebackInfo['goods_no']], [
                        'instalment_status' => OrderGivebackStatus::ZUJIN_SUCCESS,
                    ]);
                    if( !$orderGivebackResult ){
                        //事务回滚 记录日志
                        \DB::rollBack();
                        \App\Lib\Common\LogApi::debug('还机单数据修改错误',$orderGivebackResult);
                    }
                    //判断是否有请求过（芝麻支付接口）
                    $orderMiniCreditPayInfo = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::getMiniCreditPayInfo($data['out_order_no'],'FINISH',$orderGivebackInfo['giveback_no']);
                    if( $orderMiniCreditPayInfo ) {
                        $arr['out_trans_no'] = $orderMiniCreditPayInfo['out_trans_no'];
                    }else{
                        $arr['out_trans_no'] = createNo();
                    }
                    $arr = [
                        'zm_order_no'=>$data['zm_order_no'],
                        'out_order_no'=>$orderGivebackInfo['order_no'],
                        'pay_amount'=>$orderGivebackInfo['compensate_amount'],
                        'remark'=>$orderGivebackInfo['giveback_no'],
                        'app_id'=>$data['notify_app_id'],
                    ];
                    $orderCloseResult = \App\Lib\Payment\mini\MiniApi::OrderClose($arr);
                    //提交事务
                    if( $orderCloseResult['code'] == 10000 ){
                        //记录日志
                        \App\Lib\Common\LogApi::debug('扣款完成进行关闭订单请求返回成功',$orderCloseResult);
                    }else{
                        //记录日志
                        \App\Lib\Common\LogApi::debug('扣款完成进行关闭订单请求返回失败',$orderCloseResult);
                    }
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
        \DB::commit();
        echo 'success';return;
    }

    /**
     * 确认订单异步通知接口 订单创建成功异步通知
     * Author: zhangjinhui
     * @param $this->data
     * @return string
     */
    public function rentTransition(){
        $data = $this->data;
        $params = [
            'business_type'=>1,
            'business_no'=>$data['out_order_no'],
            'status'=>'success',
        ];
        //开启事务
        \DB::beginTransaction();
        $b = \App\Order\Modules\Service\OrderPayNotify::callback($params);
        if (!$b) {
            //事物回滚 记录日志
            \DB::rollBack();
            \App\Lib\Common\LogApi::debug('支付回调处理失败',$params);
            echo "小程序订单支付失败";return;
        }
        \DB::commit();
        echo 'success';return;
    }
}