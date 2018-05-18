<?php
namespace App\Order\Modules\Service;

use App\Lib\ApiStatus;

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
        $data['order_no'] = OrderOperate::createOrderNo(1);
        try{
            DB::beginTransaction();
            //var_dump($data);die;
            $order_no = OrderOperate::createOrderNo(1);
            //订单创建构造器
            $orderCreater = new OrderComponnet($order_no,$data['user_id']);

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
           // $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);

            $b = $orderCreater->filter();
            var_dump($b);
            var_dump( $orderCreater->getOrderCreater()->getError());

            $schemaData = $orderCreater->getDataSchema();
            var_dump($schemaData);
            $b = $orderCreater->create();
            die;
            DB::commit();
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
            $orderCreater = new OrderComponnet($order_no,$data['user_id']);

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
            if( $schema_data['sku']['payment_type_id']==\zuji\Config::WithhodingPay){
                if( !$schema_data['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            //租期类型格式
            $zuqi_type = "";
            if($schema_data['sku']['zuqi_type']==1){
                $zuqi_type = "day";
            }
            elseif($schema_data['sku']['zuqi_type']==2){
                $zuqi_type = "month";
            }

            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $schema_data['credit']['certified']?'Y':'N',
                'certified_platform'=> zuji\certification\Certification::getPlatformName($schema_data['credit']['certified_platform']),
                'credit'			=> ''.$schema_data['credit']['credit'],

                'credit_type'			=> $credit_type,
                'credit_status'		=> $b &&$need_to_sign_withholding=='N'&&$need_to_credit_certificate=='N'?'Y':'N',  // 是否免押金

                // 订单金额
                'amount'			=> Order::priceFormat($schema_data['sku']['amount']/100),
                // 优惠类型
                'coupon_type'	=> ''.$schema_data['coupon']['coupon_type'],
                // 优惠金额
                'discount_amount'	=> Order::priceFormat($schema_data['sku']['discount_amount']/100),
                // 商品总金额
                'all_amount'		=> Order::priceFormat($schema_data['sku']['all_amount']/100),
                // 买断价
                'buyout_price'	    => Order::priceFormat($schema_data['sku']['buyout_price']/100),
                // 市场价
                'market_price'	    => Order::priceFormat($schema_data['sku']['market_price']/100),
                //押金
                'yajin'				=> Order::priceFormat($schema_data['sku']['yajin']/100),
                //免押金
                'mianyajin'			=> Order::priceFormat($schema_data['sku']['mianyajin']/100),
                //原始租金
                'zujin'				=> Order::priceFormat($schema_data['sku']['zujin']/100),
                //首期金额
                'first_amount'				=> Order::priceFormat($schema_data['instalment']['first_amount']/100),
                //每期金额
                'fenqi_amount'				=> Order::priceFormat($schema_data['instalment']['fenqi_amount']/100),
                //意外险
                'yiwaixian'			=> Order::priceFormat($schema_data['sku']['yiwaixian']/100),
                //租期
                'zuqi'				=> ''.$schema_data['sku']['zuqi'],
                //租期类型
                'zuqi_type'			=> $zuqi_type,
                'chengse'			=> ''.$schema_data['sku']['chengse'],
                // 支付方式
                'payment_type_id'	 => ''.$schema_data['sku']['payment_type_id'],
                'contract_id'			 => ''.$schema_data['sku']['contract_id'],
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $need_to_credit_certificate,
                '_order_info' => $schema_data,
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
    public function get_order_detail($where=[]){
        return $this->orderRepository->getOrderInfo($where);



    }

}