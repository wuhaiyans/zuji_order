<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Lib\Payment\AlipayApi;
use App\Lib\Payment\UnionpayApi;
use App\Order\Modules\Repository\OrderRepository;
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
     * @return boolean
     */
    public function consume($appid,$data)
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
            $res = UnionpayApi::consume($appid,$arr);
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
    public function sendsms($appid,$data)
    {
        DB::beginTransaction();
        try {
            $order =$this->orderRepository->getInfoById($data['order_no'],$data['user_id']);
            if($order===false){
                DB::rollBack();
                return false;
            }
            if($order['trade_no']==""){
                $trade_no =createNo(3);
                $b =$this->orderRepository->updateTrade($data['order_no'], $trade_no);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
                $order['trade_no'] =$trade_no;
            }
            $arr =[
                'bankcard_id' => $data['bankcard_id'],
                'out_no' => $order['trade_no'],
                'amount'=>"",//需要支付的金额 和分期期数 （多个商品 等需求）
                'user_id' =>$data['user_id'],
                'back_url' => '', //后端回调地址
                'fenqi' => '', //分期期数 0 为不分期
            ];
            $res = UnionpayApi::sendSms($appid,$arr);
            if(!$res){
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }
     /**
     * 支付宝支付初始化处理
     * @return array
     */
    public function openAndPay($data)
    {
        $result = ['payment_url'=>'','payment_form'=>''];
        DB::beginTransaction();
        try {

            $order =$this->orderRepository->isPay($data['order_no']);
            if($order===false){
                DB::rollBack();
                return false;
            }

            if($order['trade_no']==""){
                $trade_no =createNo(3);
                $b =$this->orderRepository->updateTrade($data['order_no'], $trade_no);
                if(!$b){
                    DB::rollBack();
                    return false;
                }
                $order['trade_no'] =$trade_no;
            }

            $alipay_data =[
                'out_no'=>$order['trade_no'],
                'amount' => '1',	// 金额，单位：分
                'name' => '测试商品支付',// 支付名称
                'back_url' => 'https://alipay/Test/notify',
                'front_url' => 'https://alipay/Test/front',
                'fenqi' => 0,	// 分期数
                'user_id' => 5,// 用户ID
            ];
//            $alipay = AlipayApi::getUrl($alipay_data);
//            var_dump($alipay);die;
//            if($alipay ===false){
//                DB::rollBack();
//                return false;
//            }
            $alipay=['payment_url'=>12,'payment_form'=>3];
            DB::commit();
            return $alipay;
        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }

    }

}
