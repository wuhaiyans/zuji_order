<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;

use App\Lib\Certification;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\OrderCreater\AddressComponnet;
use App\Order\Modules\OrderCreater\ChannelComponnet;
use App\Order\Modules\OrderCreater\CouponComponnet;
use App\Order\Modules\OrderCreater\CreditComponnet;
use App\Order\Modules\OrderCreater\DepositComponnet;
use App\Order\Modules\OrderCreater\InstalmentComponnet;
use App\Order\Modules\OrderCreater\OrderComponnet;
use App\Order\Modules\OrderCreater\SkuComponnet;
use App\Order\Modules\OrderCreater\UserComponnet;
use App\Order\Modules\OrderCreater\WithholdingComponnet;
use App\Order\Modules\OrderCreater\YidunComponnet;
use App\Order\Modules\Repository\OrderRepository;
use Illuminate\Support\Facades\DB;

class OrderCreater
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * 改版下单
     * @param $data
     * [
     *'appid'=>1, //appid
     *'pay_type'=>1, //支付方式
     *'address_id'=>$address_id, //收货地址
     *'sku'=>[0=>['sku_id'=>1,'sku_num'=>2]], //商品数组
     *'coupon'=>["b997c91a2cec7918","b997c91a2cec7000"], //优惠券组信息
     *'user_id'=>18,  //增加用户ID
     *];
     */

    public function create($data){
        $orderNo = OrderOperate::createOrderNo(1);
        try{
            DB::beginTransaction();
            //var_dump($data);die;
            $order_no = OrderOperate::createOrderNo(1);
            //订单创建构造器
            $orderCreater = new OrderComponnet($orderNo,$data['user_id'],$data['pay_type']);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],$data['address_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            // 信用
            $orderCreater = new CreditComponnet($orderCreater,$data['appid']);

            //蚁盾数据
            $orderCreater = new YidunComponnet($orderCreater);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id']);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon']);

            //分期
//           $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);

            $b = $orderCreater->filter();
//            if(!$b){
//                DB::rollBack();
//                //把无法下单的原因放入到用户表中
//                $error =$orderCreater->getOrderCreater()->getError();
//                return $orderCreater->getOrderCreater()->getError();
//            }
            $schemaData = $orderCreater->getDataSchema();
          //  var_dump($schemaData);
            $b = $orderCreater->create();
            //创建成功组装数据返回结果
            if(!$b){
                DB::rollBack();
                return $orderCreater->getOrderCreater()->getError();
            }
            die;

            DB::commit();
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$schemaData['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['score'],
                'credit_status'		=> $b &&$need_to_sign_withholding=='N',
                'sku'=>$schemaData['sku'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                '_order_info' => $schemaData,
                '_error' => $orderCreater->getOrderCreater()->getError(),
                'order_no'=>$orderNo,
            ];
            return $result;

            } catch (\Exception $exc) {
                DB::rollBack();
                echo $exc->getMessage();
                die;
            }

    }
    /**
     * 订单确认查询
     * 结构 同create()方法 少个地址组件
     */
    public function confirmation($data)
    {
        try {
            //var_dump($data);die;
            $order_no = OrderOperate::createOrderNo(1);
            //订单创建构造器
            $orderCreater = new OrderComponnet($order_no,$data['user_id'],$data['pay_type']);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],8);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            // 信用
            $orderCreater = new CreditComponnet($orderCreater,$data['appid']);

            //蚁盾数据
            $orderCreater = new YidunComponnet($orderCreater);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id']);


            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon']);

            //分期
           // $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);

            $b = $orderCreater->filter();
            if(!$b){
                //把无法下单的原因放入到用户表中
                $error =$orderCreater->getOrderCreater()->getError();
                var_dump($error);
            }
            $schemaData = $orderCreater->getDataSchema();
            var_dump($schemaData);

            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$schemaData['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }

            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['score'],
                'credit_status'		=> $b &&$need_to_sign_withholding=='N',
                'sku'=>$schemaData['sku'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                '_order_info' => $schemaData,
                '$b' => $b,
                '_error' => $orderCreater->getOrderCreater()->getError(),
            ];
            return $result;
        } catch (\Exception $exc) {
            echo $exc->getMessage();
            die;
        }
    }

    /*
    *
    * 发货后，更新物流单号方法
    */
    public function updateDelivery($params){
        if(empty($params['order_no'])){
            return ApiStatus::CODE_30005;//订单编码不能为空
        }
        if(empty($params['delivery_sn'])){
            return ApiStatus::CODE_30006;//物流单号不能为空
        }
        if(empty($params['delivery_type'])){
            return ApiStatus::CODE_30007;//物流渠道不能为空
        }
        $res = $this->orderUserInfoRepository->update($params);
        if(!$res){
            return false;
        }
        return true;
    }

    /*
     *
     * 更新物流单号
     */
    public function update($params){
        return $this->orderUserInfoRepository->update($params);
    }
    //获取订单信息
    public function get_order_info($where){
        return $this->orderRepository->get_order_info($where);
    }
    //更新订单状态
    public function order_update($order_no){
        return $this->orderRepository->order_update($order_no);
    }
    public function get_order_detail($params){
        $param = filter_array($params,[
            'order_no'           => 'required',
            'wuliu_channel_id'  => 'required',
            'logistics_no'       =>'required',
            'user_id'             =>'required',
        ]);
        if(count($param)<4){
            return  ApiStatus::CODE_20001;
        }
        return $this->orderRepository->getOrderInfo($params);



    }

}