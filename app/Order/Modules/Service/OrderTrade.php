<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;
use App\Lib\Payment\AlipayApi;
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
     * 支付宝支付初始化处理
     * @return array
     */
    public function alipayInitialize($data)
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
