<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Lib\Channel\Channel;
use App\Lib\Payment\AlipayApi;
use App\Lib\Payment\UnionpayApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\PayCreater;
use Illuminate\Support\Facades\DB;

class OrderTrade
{

    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {

        $this->orderRepository = $orderRepository;

    }
    /**
     * 银联支付消费接口(限已开通银联用户)
     * $data[
     * "bankcard_id"  => "required", //银行卡id
     *"user_id"=>"required", //用户ID
     *"order_no"  => "required", //订单号
     *"sms_code"  => "required", // 短信验证码]
     * @return boolean
     */
    public function consume($data)
    {
        try {
            $order =$this->orderRepository->getInfoById($data['order_no'],$data['user_id']);
            if($order===false){
                return false;
            }
            $order =$this->orderRepository->isPay($data['order_no']);
            if($order===false){
                return false;
            }
            if($order['trade_no']==""){
                return false;
            }
            $arr =[
                'bankcard_id' => $data['bankcard_id'],
                'out_no' => $order['trade_no'],
                'sms_code'=>$data['sms_code'],
                'user_id' =>$data['user_id'],
            ];
            $res = UnionpayApi::consume($arr);
            if(!$res){
                return false;
            }
            return true;
        } catch (\Exception $exc) {
            echo $exc->getMessage();
            die;
        }

    }

    /**
     * 发送消费验证码
     * @return boolean
     */
    public function sendsms($data)
    {
        DB::beginTransaction();
        try {
            $orderInfo =$this->orderRepository->getInfoById($data['order_no'],$data['user_id']);
            if($orderInfo===false){
                DB::rollBack();
                return false;
            }
            if($orderInfo['trade_no']==""){
                $trade_no =createNo(3);
                $b =$this->orderRepository->updateTrade($data['order_no'], $trade_no);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
                $orderInfo['trade_no'] =$trade_no;
            }
            $amount =$orderInfo['order_amount']+$orderInfo['order_yajin']+$orderInfo['order_insurance'];
            $fenqi =0;
            if($orderInfo['zuqi_type']==2){
                $orderGoodsInfo =OrderRepository::getGoodsListByOrderId($data['order_no']);
                $fenqi =$orderGoodsInfo[0]['zuqi'];
            }
            // 查询
            $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_ZUJI, $orderInfo['trade_no']);
        } catch (\App\Lib\NotFoundException $exc) {

            // 创建支付
            $pay = PayCreater::createPayment([
                'user_id'		=> $data['user_id'],
                'businessType'	=> OrderStatus::BUSINESS_ZUJI,
                'businessNo'	=> $data['order_no'],

                'paymentNo' => $orderInfo['trade_no'],
                'paymentAmount' => $amount,
                'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Unionpay,
                'paymentFenqi'	=> $fenqi,
            ]);

            $step = $pay->getCurrentStep();
            //echo '当前阶段：'.$step."\n";
            $_params = [
                'name'			=> '订单支付',					//【必选】string 交易名称
                'front_url'		=> $data['return_url'],	//【必选】string 前端回跳地址
            ];
            $urlInfo = $pay->getCurrentUrl( \App\Order\Modules\Repository\Pay\Channel::Unionpay,$_params );
            //var_dump( $urlInfo );
            $alipay=['payment_url'=>$urlInfo['url'],'payment_form'=>$urlInfo['_data']];
            DB::commit();
            return $alipay;
//            $arr =[
//                'bankcard_id' => $data['bankcard_id'],
//                'out_no' => $orderInfo['trade_no'],
//                'amount'=>"",//需要支付的金额 和分期期数 （多个商品 等需求）
//                'user_id' =>$data['user_id'],
//                'back_url' => '', //后端回调地址
//                'fenqi' => '', //分期期数 0 为不分期
//            ];
//            $res = UnionpayApi::sendSms($arr);
//            if(!$res){
//                DB::rollBack();
//                return false;
//            }

        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }
     /**
      * 支付宝支付初始化处理
      * $data[
      *     'user_id'=>'',//用户ID
      *      'return_url'=>'',//前端回调地址
      *      'order_no'=>'', //订单编号
      * ]
     * @return array
     */
    public function alipayInitialize($data)
    {
        DB::beginTransaction();
        try {

            $order =$this->orderRepository->isPay($data['order_no'],$data['user_id']);
            if($order===false){
                DB::rollBack();
                return false;
            }
            $orderInfo =OrderRepository::getOrderInfo(['order_no'=>$data['order_no']]);

            if($orderInfo===false){
                DB::rollBack();
                return false;
            }

            if($orderInfo['trade_no']==""){
                $trade_no =createNo(3);
                $b =$this->orderRepository->updateTrade($data['order_no'], $trade_no);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
                $orderInfo['trade_no'] =$trade_no;
            }
            $amount =$orderInfo['order_amount']+$orderInfo['order_yajin']+$orderInfo['order_insurance'];
            $fenqi =0;
            if($orderInfo['zuqi_type']==2){
                $orderGoodsInfo =OrderRepository::getGoodsListByOrderId($data['order_no']);
                $fenqi =$orderGoodsInfo[0]['zuqi'];
            }
            // 查询
            $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_ZUJI, $orderInfo['trade_no']);
            } catch (\App\Lib\NotFoundException $exc) {

                // 创建支付
                $pay = PayCreater::createPayment([
                    'user_id'		=> $data['user_id'],
                    'businessType'	=> OrderStatus::BUSINESS_ZUJI,
                    'businessNo'	=> $data['order_no'],

                    'paymentNo' => $orderInfo['trade_no'],
                    'paymentAmount' => $amount,
                    'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
                    'paymentFenqi'	=> $fenqi,
                ]);
                $step = $pay->getCurrentStep();
                //echo '当前阶段：'.$step."\n";

                $_params = [
                    'name'			=> '订单支付',					//【必选】string 交易名称
                    'front_url'		=> $data['return_url'],	//【必选】string 前端回跳地址
                ];
                $urlInfo = $pay->getCurrentUrl(\App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
                //var_dump( $urlInfo );
                DB::commit();
                return $urlInfo;
            } catch (\Exception $exc) {
                DB::rollBack();
                echo $exc->getMessage();
                die;
            }

    }
    /**
     * 支付宝资金预授权
     * $data[
     *     'user_id'=>'',//用户ID
     *      'return_url'=>'',//前端回调地址
     *      'order_no'=>'', //订单编号
     * ]
     * @return array
     */
    public function alipayFundAuth($data)
    {
        DB::beginTransaction();
        try {

            $order =$this->orderRepository->isPay($data['order_no'],$data['user_id']);
            if($order===false){
                DB::rollBack();
                return false;
            }
            $orderInfo =OrderRepository::getOrderInfo(['order_no'=>$data['order_no']]);

            if($orderInfo===false){
                DB::rollBack();
                return false;
            }

            if($orderInfo['trade_no']==""){
                $trade_no =createNo(3);
                $b =$this->orderRepository->updateTrade($data['order_no'], $trade_no);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
                $orderInfo['trade_no'] =$trade_no;
            }
            $amount = $orderInfo['order_yajin'];
            // 查询
            $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_ZUJI, $orderInfo['trade_no']);
        } catch (\App\Lib\NotFoundException $exc) {

            // 创建支付
            $pay = PayCreater::createPayment([
                'user_id'		=> $data['user_id'],
                'businessType'	=> OrderStatus::BUSINESS_ZUJI,
                'businessNo'	=> $data['order_no'],

                'fundauthNo' => \createNo(3),
                'fundauthAmount' => $amount,
                'fundauthChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
            ]);
            $step = $pay->getCurrentStep();
            //echo '当前阶段：'.$step."\n";

            $_params = [
                'name'			=> '订单预授权',					//【必选】string 交易名称
                'front_url'		=> $data['return_url'],	//【必选】string 前端回跳地址
            ];
            $urlInfo = $pay->getCurrentUrl(\App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
            //var_dump( $urlInfo );
            DB::commit();
            return $urlInfo;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }

}
