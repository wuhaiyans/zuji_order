<?php
namespace App\Order\Modules\Service;
use App\Lib\ApiStatus;
use App\Lib\Certification;
use App\Lib\Common\JobQueueApi;
use App\Lib\Common\SmsApi;
use App\Lib\User\User;
use App\Order\Models\Order;
use App\Order\Models\OrderLog;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\OrderCreater\AddressComponnet;
use App\Order\Modules\OrderCreater\ChannelComponnet;
use App\Order\Modules\OrderCreater\CouponComponnet;
use App\Order\Modules\OrderCreater\DepositComponnet;
use App\Order\Modules\OrderCreater\InstalmentComponnet;
use App\Order\Modules\OrderCreater\OrderComponnet;
use App\Order\Modules\OrderCreater\RiskComponnet;
use App\Order\Modules\OrderCreater\SkuComponnet;
use App\Order\Modules\OrderCreater\UserComponnet;
use App\Order\Modules\OrderCreater\WithholdingComponnet;
use App\Order\Modules\PublicInc;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ShortMessage\OrderCreate;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCreater
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * 线上下单
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
        $orderType =OrderStatus::orderOnlineService;
        try{
            DB::beginTransaction();
            $order_no = OrderOperate::createOrderNo(1);
            //订单创建构造器
            $orderCreater = new OrderComponnet($orderNo,$data['user_id'],$data['pay_type'],$data['appid'],$orderType);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],$data['address_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控
            $orderCreater = new RiskComponnet($orderCreater,$data['appid']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id']);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);

            //分期
           $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);

           $b = $orderCreater->filter();
//            if(!$b){
//                DB::rollBack();
//                //把无法下单的原因放入到用户表中
//                User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
//                set_msg($orderCreater->getOrderCreater()->getError());
//                return false;
//            }
            $schemaData = $orderCreater->getDataSchema();

            $b = $orderCreater->create();
            //创建成功组装数据返回结果
            if(!$b){
                DB::rollBack();
                set_msg($orderCreater->getOrderCreater()->getError());
                return false;
            }
            DB::commit();
            // 是否需要签署代扣协议
            $need_to_sign_withholding = 'N';
            if( $data['pay_type']== PayInc::WithhodingPay){
                if( !$schemaData['withholding']['withholding_no'] ){
                    $need_to_sign_withholding = 'Y';
                }
            }
            $result = [
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['score'],
                'credit_status'		=> $b && $need_to_sign_withholding=='N',
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $schemaData['user']['certified']?'N':'Y',
                '_order_info' => $schemaData,
                'order_no'=>$orderNo,
                'pay_type'=>$data['pay_type'],
            ];
           // 创建订单后 发送支付短信。;
//            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_CREATE);
//            $orderNoticeObj->notify();
            //发送取消订单队列
        $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderCancel_".$orderNo,config("tripartite.API_INNER_URL"), [
            'method' => 'api.inner.miniCancelOrder',
            'order_no'=>$orderNo,
            'user_id'=>$data['user_id'],
            'time' => time(),
        ],time()+7200,"");
            OrderLogRepository::add($data['user_id'],$schemaData['user']['user_mobile'],\App\Lib\PublicInc::Type_User,$orderNo,"下单","用户下单");
            return $result;

            } catch (\Exception $exc) {
                DB::rollBack();
                echo $exc->getMessage();
                die;
            }

    }
    /**
     * 小程序下单
     * @param $data
     * [
     *'appid'=>1, //appid
     *'order_no'=>1, //临时订单号
     *'address_id'=>$address_id, //收货地址
     *'sku'=>[0=>['sku_id'=>1,'sku_num'=>2]], //商品数组
     *'coupon'=>["b997c91a2cec7918","b997c91a2cec7000"], //优惠券组信息
     *'user_id'=>18,  //增加用户ID
     *];
     */
    public function miniCreate($data){
        print_r($data);die;
        try{
            DB::beginTransaction();
            $orderType =OrderStatus::orderMiniService;
            //订单创建构造器
            $orderCreater = new OrderComponnet($data['order_no'],$data['user_id'],$data['pay_type'],$data['appid'],$orderType);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],0,$data['address_info']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控
            $orderCreater = new RiskComponnet($orderCreater,$data['appid']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type'],$data['credit_amount']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id']);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);

            //分期
            $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);
            $b = $orderCreater->filter();
            if(!$b){
                DB::rollBack();
                //把无法下单的原因放入到用户表中
                User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
                set_msg($orderCreater->getOrderCreater()->getError());
                return false;
            }
            $schemaData = $orderCreater->getDataSchema();
            $b = $orderCreater->create();
            //创建成功组装数据返回结果
            if(!$b){
                DB::rollBack();
                //把无法下单的原因放入到用户表中
                User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
                set_msg($orderCreater->getOrderCreater()->getError());
                return false;
            }
            DB::commit();
            $result = [
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['score'],
                '_order_info' => $schemaData,
                'order_no'=>$data['order_no'],
                'pay_type'=>$data['pay_type'],
            ];
            // 创建订单后 发送支付短信。;
            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$data['order_no'],SceneConfig::ORDER_CREATE);
            $orderNoticeObj->notify();
            //发送取消订单队列（小程序取消订单队列）
            $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderCancel_".$data['order_no'],config("tripartite.API_INNER_URL"), [
                'method' => 'api.inner.cancelOrder',
//                'order_no'=>$data['order_no'],
//                'user_id'=>$data['user_id'],
//                'time' => time(),
            ],time()+1800,"");
            OrderLogRepository::add($data['user_id'],$schemaData['user']['user_mobile'],\App\Lib\PublicInc::Type_User,$data['order_no'],"下单","用户下单");
            return $result;

        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            die;
        }
    }

    public static function dataSchemaFormate($schemaData){

        $first_amount =0;
        if($schemaData['order']['zuqi_type'] ==1){
            //短租
            foreach ($schemaData['sku'] as $key=>$value){
                foreach ($value['instalment'] as $k=>$v){
                    $first_amount+=$v['amount'];
                }
                $schemaData['sku'][$key]['first_amount'] =$first_amount;
            }
        }else{
            //长租
            foreach ($schemaData['sku'] as $key=>$value){

                $schemaData['sku'][$key]['first_amount'] =$value['instalment'][0]['amount'];
            }

        }

        return $schemaData;


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
            $orderCreater = new OrderComponnet($order_no,$data['user_id'],$data['pay_type'],$data['appid'],OrderStatus::orderOnlineService);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控
            $orderCreater = new RiskComponnet($orderCreater,$data['appid']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id']);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);

            //分期
            $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);

            $b = $orderCreater->filter();
            if(!$b){
                //把无法下单的原因放入到用户表中
                $userRemark =User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());

            }
            $schemaData = self::dataSchemaFormate($orderCreater->getDataSchema());
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
                'credit_status'		=> $b && $need_to_sign_withholding=='N',
                // 是否需要 签收代扣协议
                'need_to_sign_withholding'	 => $need_to_sign_withholding,
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $schemaData['user']['certified']?'N':'Y',
                '_order_info' => $schemaData,
                'b' => $b,
                '_error' => $orderCreater->getOrderCreater()->getError(),
            ];
            return $result;
        } catch (\Exception $exc) {
            echo $exc->getMessage();
            die;
        }
    }

    /**
     * 订单确认查询
     * 结构 同create()方法 少个地址组件
     */
    public function miniConfirmation($data)
    {
        print_r($data);die;
        try{
            $orderType =OrderStatus::orderMiniService;
            $data['user_id'] = intval($data['user_id']);
            $data['pay_type'] = intval($data['pay_type']);
            $data['appid'] = intval($data['appid']);
            //订单创建构造器
            $orderCreater = new OrderComponnet($data['order_no'],($data['user_id']),($data['pay_type']),($data['appid']),($orderType));

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],0,$data['address_info']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控
            $orderCreater = new RiskComponnet($orderCreater,$data['appid']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type'],$data['credit_amount']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id']);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);

            //分期
            $orderCreater = new InstalmentComponnet($orderCreater,$data['pay_type']);

            $b = $orderCreater->filter();
            if(!$b){
                //把无法下单的原因放入到用户表中
                $userRemark =User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
            }
            $schemaData = $orderCreater->getDataSchema();
            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['score'],
                '_order_info' => $schemaData,
                'b' => $b,
                '_error' => $orderCreater->getOrderCreater()->getError(),
                'pay_type'=>$data['pay_type'],
            ];
            return $result;
        } catch (\Exception $exc) {
            DB::rollBack();
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
        if(empty($params['order_no'])){
            return ApiStatus::CODE_30005;//订单编码不能为空
        }
        if(empty($params['delivery_sn'])){
            return ApiStatus::CODE_30006;//物流单号不能为空
        }
        if(empty($params['delivery_type'])){
            return ApiStatus::CODE_30007;//物流渠道不能为空
        }
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