<?php
namespace App\Order\Modules\Service;
use App\Activity\Modules\Inc\DestineStatus;
use App\Activity\Modules\Repository\Activity\ExperienceDestine;
use App\Activity\Modules\Repository\ExperienceDestineRepository;
use App\Lib\ApiStatus;
use App\Lib\Certification;
use App\Lib\Common\JobQueueApi;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use App\Lib\User\User;
use App\Order\Models\Order;
use App\Order\Models\OrderLog;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\OrderCreater\ActivityComponnet;
use App\Order\Modules\OrderCreater\AddressComponnet;
use App\Order\Modules\OrderCreater\ChannelComponnet;
use App\Order\Modules\OrderCreater\CouponComponnet;

use App\Order\Modules\OrderCreater\DepositComponnet;
use App\Order\Modules\OrderCreater\InstalmentComponnet;
use App\Order\Modules\OrderCreater\OrderComponnet;
use App\Order\Modules\OrderCreater\OrderPayComponnet;
use App\Order\Modules\OrderCreater\ReceiveCouponComponnet;
use App\Order\Modules\OrderCreater\RiskComponnet;
use App\Order\Modules\OrderCreater\SkuComponnet;
use App\Order\Modules\OrderCreater\StoreAddressComponnet;
use App\Order\Modules\OrderCreater\UserComponnet;
use App\Order\Modules\OrderCreater\WithholdingComponnet;
use App\Order\Modules\PublicInc;
use App\Order\Modules\Repository\Order\OrderScheduleOnce;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\ShortMessage\OrderCreate;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Order\Modules\Repository\Pay\WithholdQuery;

class OrderCreater
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * 线上下单 创建订单
     * @author wuhaiyan
     * @param $data
     * [
     *      'appid'	=> '',	            //【必选】int 渠道入口
     *		'pay_channel_id'	=> '',	//【必选】int 支付支付渠道
     *		'pay_type'	=> '',	        //【必选】int 支付方式
     *		'address_id'	=> '',	    //【必选】int 用户收货地址
     *		'sku_info'	=> [	        //【必选】array	SKU信息
     *			[
     *				'sku_id' => '',		//【必选】 int SKU ID
     *				'sku_num' => '',	//【必选】 int SKU 数量
     *              'begin_time'=>'',   //【短租必须】string 租用开始时间
     *              'end_time'=>'',     //【短租必须】string 租用结束时间
     *			]
     *		]',
     *		'coupon'	=> [1,1],	//【可选】array 优惠券
     *      $userinfo [
     *          'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *          'user_id'=>1,   //【必须】int用户ID
     *          'user_name'=>1, //【必须】string用户名
     *          'mobile'=>1,    //【必须】string手机号
     *      ]
     * @return array
     */

    public function create($data){

        $orderType = OrderStatus::getOrderTypeId(['pay_type'=>$data['pay_type'],'destine_no'=>$data['destine_no']]);

        $orderNo = OrderOperate::createOrderNo($orderType);

        try{

            //订单创建构造器
            $orderCreater = new OrderComponnet($orderNo,$data['user_id'],$data['appid'],$orderType);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],$data['address_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控
            $orderCreater = new RiskComponnet($orderCreater);

            //活动
            $orderCreater = new ActivityComponnet($orderCreater,$data['destine_no']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);

            //押金
           $orderCreater = new DepositComponnet($orderCreater);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //门店地址
            $orderCreater = new StoreAddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //分期
            $orderCreater = new InstalmentComponnet($orderCreater);

            //支付
            $orderCreater = new OrderPayComponnet($orderCreater,$data['user_id']);


            //调用各个组件 过滤一些参数 和无法下单原因
            $b = $orderCreater->filter();
            if(!$b){
                DB::rollBack();
                //把无法下单的原因放入到用户表中
                User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
                set_msg($orderCreater->getOrderCreater()->getError());
                return false;
            }
            $schemaData = $orderCreater->getDataSchema();

            DB::beginTransaction();
            //调用各个组件 创建方法
            $b = $orderCreater->create();
            //创建成功组装数据返回结果
            if(!$b){
                DB::rollBack();
                set_msg($orderCreater->getOrderCreater()->getError());
                return false;
            }

            DB::commit();
            //组合数据
            $result = [
                'pay_type'=>$data['pay_type'],
                'order_no'=>$orderNo,
                'pay_info'=>$schemaData['pay_info'],
                'app_id'=>$data['appid'],

            ];
           // 创建订单后 发送支付短信。;
            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_CREATE);
            $orderNoticeObj->notify();

            //发送订单消息队列
            $schedule = new OrderScheduleOnce(['user_id'=>$data['user_id'],'order_no'=>$orderNo]);
            //发送订单风控信息保存队列
            $schedule->OrderRisk();
            //发送取消订单队列
            $schedule->CancelOrder();
            //发送订单押金信息返回风控系统
            $schedule->YajinReduce();
            //推送到区块链
            $b =OrderBlock::orderPushBlock($orderNo,OrderBlock::OrderUnPay);
            LogApi::info("OrderCreate-addOrderBlock:".$orderNo."-".$b);
            if($b){
                LogApi::error("OrderCreate-addOrderBlock:".$orderNo."-".$b);
                LogApi::alert("OrderCreate-addOrderBlock:".$orderNo."-".$b,[],[config('web.order_warning_user')]);
            }


//            $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderRisk_".$orderNo,config("ordersystem.ORDER_API")."/OrderRisk", [
//                'method' => 'api.inner.orderRisk',
//                'order_no'=>$orderNo,
//                'user_id'=>$data['user_id'],
//                'time' => time(),
//            ],time()+60,"");
//
//
//
//        $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderCancel_".$orderNo,config("ordersystem.ORDER_API")."/CancelOrder", [
//            'method' => 'api.inner.cancelOrder',
//            'order_no'=>$orderNo,
//            'user_id'=>$data['user_id'],
//            'time' => time(),
//        ],time()+config('web.order_cancel_hours'),"");
            //增加操作日志
            OrderLogRepository::add($data['user_id'],$schemaData['user']['user_mobile'],\App\Lib\PublicInc::Type_User,$orderNo,"下单","用户下单");

            return $result;

            } catch (\Exception $exc) {
                DB::rollBack();
                LogApi::error("OrderCreate-Exception:".$exc->getMessage());
                set_msg($exc->getMessage());
                return false;
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
        try{
            DB::beginTransaction();
            $orderType =OrderStatus::orderMiniService;
            //订单创建构造器
            $orderCreater = new OrderComponnet($data['order_no'],$data['user_id'],$data['appid'],$orderType);
            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],$data['address_id']);
            $orderCreater->setUserComponnet($userComponnet);
            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);
            //风控(小程序风控信息接口不处理)
            $orderCreater = new RiskComponnet($orderCreater);
            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type'],$data['credit_amount']);
            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);
            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);
            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);
            //分期
            $orderCreater = new InstalmentComponnet($orderCreater);
            $b = $orderCreater->filter();
            if(!$b){
                DB::rollBack();
                //把无法下单的原因放入到用户表中
                User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
                set_msg(ApiStatus::CODE_35017);
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
                'credit'			=> ''.$schemaData['user']['credit'],
                '_order_info' => $schemaData,
                'order_no'=>$data['order_no'],
                'pay_type'=>$data['pay_type'],
            ];
            // 创建订单后 发送支付短信。;
            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$data['order_no'],SceneConfig::ORDER_CREATE);
            $orderNoticeObj->notify();

            //发送订单消息队列
            $schedule = new OrderScheduleOnce(['user_id'=>$data['user_id'],'order_no'=>$data['order_no']]);
            //发送订单风控信息保存队列
            $schedule->OrderRisk();
            //发送取消订单队列
            $schedule->miniCancelOrder();
            //发送订单押金信息返回风控系统
            $schedule->YajinReduce();
            //推送到区块链
            $b =OrderBlock::orderPushBlock($data['order_no'],OrderBlock::OrderUnPay);
            LogApi::info("OrderCreate-addOrderBlock:".$data['order_no']."-".$b);
            if($b){
                LogApi::error("OrderCreate-addOrderBlock:".$data['order_no']."-".$b);
            }


//            //发送订单风控信息保存队列
//            $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderRisk_".$data['order_no'],config("ordersystem.ORDER_API")."/OrderRisk", [
//                'method' => 'api.inner.orderRisk',
//                'order_no'=>$data['order_no'],
//                'user_id'=>$data['user_id'],
//                'time' => time(),
//            ],time()+60,"");
//
//
////            发送取消订单队列（小程序取消订单队列）
//            $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderCancel_".$data['order_no'],config("ordersystem.ORDER_API")."/CancelOrder", [
//                'method' => 'api.inner.miniCancelOrder',
//                'order_no'=>$data['order_no'],
//                'user_id'=>$data['user_id'],
//                'time' => time(),
//            ],time()+config('web.mini_order_cancel_hours'),"");
            OrderLogRepository::add($data['user_id'],$schemaData['user']['user_mobile'],\App\Lib\PublicInc::Type_User,$data['order_no'],"下单","用户下单");
            return $result;

        } catch (\Exception $exc) {
            DB::rollBack();
            echo $exc->getMessage();
            set_msg($exc->getMessage());
            die;
        }
    }

    /**
     * 过滤一些分期的数据
     * @author wuhaiyan
     * @param $schemaData array 订单组装的数据
     * @return array
     */

    public static function dataSchemaFormate($schemaData){

        $first_amount =0;
        $total_amount =0;
        $totalAmount =0;
        $payType =$schemaData['order']['pay_type'];
        foreach ($schemaData['sku'] as $key=>$value) {

            $amount =  normalizeNum($schemaData['sku'][$key]['amount_after_discount']); //总租金
            $zuqi =$schemaData['sku'][$key]['zuqi']; //租期
            $insurance =normalizeNum($value['insurance']); //意外险

            foreach ($value['instalment'] as $k=>$v){
                $totalAmount +=$v['amount'];
            }
            $totalAmount =normalizeNum($totalAmount);
            $schemaData['sku'][$key]['instalment_total_amount'] = $totalAmount;

            //固定优惠券 每期金额
            $month = intval($amount/$zuqi);
            //除不尽的放到首月
            $first = $amount-$month*$zuqi+$month;
            //代扣+预授权 ，小程序发的分期信息
            if ($payType == PayInc::WithhodingPay || $payType == PayInc::MiniAlipay || $payType == PayInc::FlowerFundauth) {
                if ($schemaData['order']['zuqi_type'] == 1) {
                    $schemaData['sku'][$key]['month_amount'] = normalizeNum($value['instalment'][0]['amount']); //每期支付金额
                    $schemaData['sku'][$key]['first_amount'] = normalizeNum($value['instalment'][0]['amount']); //首期支付金额
                }else{
                    $schemaData['sku'][$key]['month_amount'] = normalizeNum($value['instalment'][1]['amount']); //每期支付金额
                    $schemaData['sku'][$key]['first_amount'] = normalizeNum($value['instalment'][0]['amount']); //首期支付金额
                }



            } //乐百分支付的分期信息
            elseif ($payType == PayInc::LebaifenPay) {
                $schemaData['sku'][$key]['month_amount'] = normalizeNum($amount/$zuqi); //每期支付金额（包含碎屏保）
                $schemaData['sku'][$key]['first_amount'] = normalizeNum($amount/$zuqi + $insurance); //首期支付金额
            }
            //（花呗） 分期信息
            elseif ($payType == PayInc::PcreditPayInstallment){
                if ($schemaData['order']['zuqi_type'] == 1) {
                    $schemaData['sku'][$key]['month_amount'] = $totalAmount;
                    $schemaData['sku'][$key]['first_amount'] = $totalAmount;
                } else {
                    $schemaData['sku'][$key]['month_amount'] = normalizeNum(($amount+$insurance)/$zuqi); //每期支付金额
                    $schemaData['sku'][$key]['first_amount'] = normalizeNum(($amount+$insurance)/$zuqi); //首期支付金额
                }
            }
            //支付宝小程序支付
            elseif ($payType == PayInc::MiniAlipay){

                if ($schemaData['order']['zuqi_type'] == 1) {
                    $schemaData['sku'][$key]['month_amount'] = $totalAmount;
                    $schemaData['sku'][$key]['first_amount'] = $totalAmount;
                } else {
                    $schemaData['sku'][$key]['month_amount'] = normalizeNum(($amount+$insurance)/$zuqi); //每期支付金额
                    $schemaData['sku'][$key]['first_amount'] = normalizeNum(($amount+$insurance)/$zuqi); //首期支付金额
                }

            }
            //其他支付方式
            else {
                if ($schemaData['order']['zuqi_type'] == 1) {
                    $schemaData['sku'][$key]['month_amount'] = $totalAmount;
                    $schemaData['sku'][$key]['first_amount'] = $totalAmount;
                } else {
                    $schemaData['sku'][$key]['month_amount'] = normalizeNum($month); //每期支付金额
                    $schemaData['sku'][$key]['first_amount'] = normalizeNum($first+$insurance);; //首期支付金额+碎屏险
                }
            }
        }

        return $schemaData;


    }

    /**
     * 订单确认查询
     * 结构 同create()方法 少个地址组件
     * @author wuhaiyan
     * @param $data
     * [
     *      'appid'	=> '',	            //【必选】int 渠道入口
     *		'pay_channel_id'	=> '',	//【必选】int 支付支付渠道
     *		'sku_info'	=> [	        //【必选】array	SKU信息
     *			[
     *				'sku_id' => '',		//【必选】 int SKU ID
     *				'sku_num' => '',	//【必选】 int SKU 数量
     *              'begin_time'=>'',   //【短租必须】string 租用开始时间
     *              'end_time'=>'',     //【短租必须】string 租用结束时间
     *			]
     *		]',
     *		'coupon'	=> [1,1],	//【可选】array 优惠券
     *      $userinfo [
     *          'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *          'user_id'=>1,   //【必须】int用户ID
     *          'user_name'=>1, //【必须】string用户名
     *          'mobile'=>1,    //【必须】string手机号
     *      ]
     * @return array
     */
    public function confirmation($data)
    {
        try {
            $orderType = OrderStatus::getOrderTypeId(['pay_type'=>0,'destine_no'=>$data['destine_no']]);

            $order_no = OrderOperate::createOrderNo($orderType);
            //订单创建构造器
            $orderCreater = new OrderComponnet($order_no,$data['user_id'],$data['appid'],$orderType);

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控
            $orderCreater = new RiskComponnet($orderCreater);

            //自动领取优惠券
            $orderCreater = new ReceiveCouponComponnet($orderCreater,$data['coupon'],$data['user_id']);
            $schema = $orderCreater->getDataSchema();

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$schema['receive_coupon']['coupon'],$data['user_id']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //分期
            $orderCreater = new InstalmentComponnet($orderCreater);

            //支付
            $orderCreater = new OrderPayComponnet($orderCreater,$data['user_id']);

            //调用各个组件 过滤方法
            $b = $orderCreater->filter();
            if(!$b){
                //把无法下单的原因放入到用户表中
                $userRemark =User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());

            }

            //调用过滤的参数方法
            $schemaData = self::dataSchemaFormate($orderCreater->getDataSchema());

            $result = [
                //'coupon'         => $data['coupon'],
                //'certified'			=> $schemaData['user']['certified']?'Y':'N',
                //'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['credit'],
               // 'credit_status'		=> $b,
                //支付方式
                'pay_type'=>$schemaData['order']['pay_type'],
                // 是否需要 信用认证
               // 'need_to_credit_certificate'			=> $schemaData['user']['certified']?'N':'Y',
              //  'pay_info'=>$schemaData['pay_info'],
                'b' => $b,
                '_error' => $orderCreater->getOrderCreater()->getError(),
                '_error_code' =>get_code(),
            ];
            $result['_order_info']['order']=[
                'pay_type'=>$schemaData['order']['pay_type'],
                'zuqi_type_name'=>$schemaData['order']['zuqi_type_name'],
            ];
            $result['_order_info']['coupon']=$schemaData['coupon'];
            $result['_order_info']['instalment'][0]=[
                'pay_type'=>$schemaData['order']['pay_type'],
                'zuqi_type_name'=>$schemaData['order']['zuqi_type_name'],
            ];
            $result['_order_info']['user']=[
                'cert_no'=>$schemaData['user']['cert_no'],
                'realname'=>$schemaData['user']['realname'],
                'user_mobile'=>$schemaData['user']['user_mobile'],
            ];
            $result['_order_info']['sku'][0]=[
                'all_amount'=>$schemaData['sku'][0]['all_amount'],
                'amount'=>$schemaData['sku'][0]['amount'],
                'discount_amount'=>$schemaData['sku'][0]['discount_amount'],
                'mianyajin'=>$schemaData['sku'][0]['mianyajin'],
                'zujin'=>$schemaData['sku'][0]['zujin'],
                'yajin'=>$schemaData['sku'][0]['yajin'],
                'deposit_yajin'=>$schemaData['sku'][0]['deposit_yajin'],
                'jianmian'=>$schemaData['sku'][0]['jianmian'],
                'first_amount'=>$schemaData['sku'][0]['first_amount'],
                'zuqi'=>$schemaData['sku'][0]['zuqi'],
                'market_price'=>$schemaData['sku'][0]['market_price'],
                'buyout_price'=>$schemaData['sku'][0]['buyout_price'],
                'amount_after_discount'=>$schemaData['sku'][0]['amount_after_discount'],
                'insurance'=>$schemaData['sku'][0]['insurance'],
                'order_coupon_amount'=>$schemaData['sku'][0]['order_coupon_amount'],
                'first_coupon_amount'=>$schemaData['sku'][0]['first_coupon_amount'],
                'instalment'=>isset($schemaData['sku'][0]['instalment'])?$schemaData['sku'][0]['instalment']:[],
                'specs'=>$schemaData['sku'][0]['specs'],
                'sku_id'=>$schemaData['sku'][0]['sku_id'],
                'spu_name'=>$schemaData['sku'][0]['spu_name'],
                'thumb'=>$schemaData['sku'][0]['thumb'],
                'total_zujin'=>$schemaData['sku'][0]['total_zujin'],
                'category_id'=>$schemaData['sku'][0]['category_id'],
                'zuqi_type'=>$schemaData['sku'][0]['zuqi_type'],
                'pay_type'=>$schemaData['sku'][0]['pay_type'],
                'begin_time'=>isset($data['sku'][0]['begin_time'])?$data['sku'][0]['begin_time']:"",
                'end_time'=>isset($data['sku'][0]['end_time'])?$data['sku'][0]['end_time']:"",
                'zuqi_type_name'=>$schemaData['sku'][0]['zuqi_type_name'],
                'instalment_total_amount'=>$schemaData['sku'][0]['instalment_total_amount'],
                'month_amount'=>$schemaData['sku'][0]['month_amount'],
                'sku_num'=>$schemaData['sku'][0]['sku_num'],

            ];
            return $result;
        } catch (\Exception $exc) {
            LogApi::info("ConfirmationOrder-Exception：".$exc->getMessage());
             set_msg($exc->getMessage());
            return false;
        }
    }

    /**
     * 订单确认查询
     * 结构 同create()方法 少个地址组件
     */
    public function miniConfirmation($data)
    {
        try{
            $orderType =OrderStatus::orderMiniService;
            $data['user_id'] = intval($data['user_id']);
            $data['pay_type'] = intval($data['pay_type']);
            $data['appid'] = intval($data['appid']);
            //订单创建构造器
            $orderCreater = new OrderComponnet($data['order_no'],($data['user_id']),($data['appid']),($orderType));

            // 用户
            $userComponnet = new UserComponnet($orderCreater,$data['user_id'],$data['address_id']);
            $orderCreater->setUserComponnet($userComponnet);

            // 商品
            $skuComponnet = new SkuComponnet($orderCreater,$data['sku'],$data['pay_type']);
            $orderCreater->setSkuComponnet($skuComponnet);

            //风控(小程序风控信息接口不处理)
            $orderCreater = new RiskComponnet($orderCreater);

            //押金

            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type'],$data['credit_amount']);

            //代扣
            //$orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id']);

            //收货地址
            $orderCreater = new AddressComponnet($orderCreater);

            //渠道
            $orderCreater = new ChannelComponnet($orderCreater,$data['appid']);

            //优惠券
            $orderCreater = new CouponComponnet($orderCreater,$data['coupon'],$data['user_id']);
            //分期
            $orderCreater = new InstalmentComponnet($orderCreater);

            $b = $orderCreater->filter();
            if(!$b){
                //把无法下单的原因放入到用户表中
                $userRemark =User::setRemark($data['user_id'],$orderCreater->getOrderCreater()->getError());
            }
            $schemaData = self::dataSchemaFormate($orderCreater->getDataSchema());
            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['credit'],
                '_order_info' => $schemaData,
                'b' => $b,
                '_error' => $orderCreater->getOrderCreater()->getError(),
                'pay_type'=>$data['pay_type'],
                '_error_code' =>get_code(),
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
	
	/**
	 * 创建支付单  ---  旧系统 导入新订单系统用  后期 可以删除
	 * @param array $param 创建支付单数组
	 * $param = [<br/>
	 *		'payType' => '',//支付方式 【必须】<br/>
	 *		'payChannelId' => '',//支付渠道 【必须】<br/>
	 *		'userId' => '',//业务用户ID 【必须】<br/>
	 *		'businessType' => '',//业务类型（租机业务 ）【必须】<br/>
	 *		'businessNo' => '',//业务编号（订单编号）【必须】<br/>
	 *		'paymentAmount' => '',//Price 支付金额（总租金），单位：元【必须】<br/>
	 *		'fundauthAmount' => '',//Price 预授权金额（押金），单位：元【必须】<br/>
	 *		'paymentFenqi' => '',//int 分期数，取值范围[0,3,6,12]，0：不分期【必须】<br/>
	 * ]<br/>
	 * @return mixed boolen：flase创建失败|array $result 结果数组
	 * $result = [<br/>
	 *		'isPay' => '',订单是否需要支付（true：需要支付；false：无需支付）【订单是否创建支付单】//<br/>
	 *		'withholdStatus' => '',是否需要签代扣（true：需要签约代扣；false：无需签约代扣）//<br/>
	 *		'paymentStatus' => '',是否需要支付（true：需要支付；false:无需支付）//<br/>
	 *		'fundauthStatus' => '',是否需要预授权（true：需要预授权；false：无需预授权）//<br/>
	 * ]
	 */
	public static function createPay( $param ) {
		//-+--------------------------------------------------------------------
		// | 校验参数
		//-+--------------------------------------------------------------------

		if( !self::__praseParam($param) ){
			return false;
		}
		//默认需要支付
		$data['isPay'] =true;
		//-+--------------------------------------------------------------------
		// | 判断租金支付方式（分期/代扣）
		//-+--------------------------------------------------------------------
		//代扣方式支付租金
		if( $param['payType'] == PayInc::WithhodingPay ){
			//然后判断预授权然后创建相关支付单
			$result = self::__withholdFundAuth($param);
			//分期支付的状态为false
			$data['paymentStatus'] = false;
		}
		//分期方式支付租金
		elseif( $param['payType'] == PayInc::FlowerStagePay || $param['payType'] == PayInc::UnionPay ){
			//然后判断预授权然后创建相关支付单
			$result = self::__paymentFundAuth($param);
			//代扣支付的状态为false
			$data['withholdStatus'] = false;
			//代扣支付的状态为false
			$data['paymentStatus'] = true;
		}
		//暂无其他支付
		else{
			return false;
		}
		//判断支付单创建结果
		if( !$result ){
			return false;
		}
		//array_merge两个参数位置不可颠倒
		return array_merge( $data,$result);
	}
	
	/**
	 * 判断代扣->预授权
	 * @param type $param
	 */
	public static function __withholdFundAuth($param) {
		//记录最终结果
		$result = [];
		//判断是否已经签约了代扣
		try{
			$withhold = WithholdQuery::getByUserChannel($param['userId'],$param['payChannelId']);
			//已经签约代扣的进行代扣和订单的绑定
			$params =[
                'business_type' =>$param['businessType'],  // 【必须】int    业务类型
                'business_no'  =>$param['businessNo'],  // 【必须】string  业务编码
            ];
            $b =$withhold->bind($params);
			//签约代扣和订单绑定失败
            if(!$b){
                return false;
            }
			$result['withholdStatus'] = false;
		}catch(\Exception $e){
			$result['withholdStatus'] = true;
		}
		//需要签约代扣+预授权金额为0 【创建签约代扣的支付单】
		if( $result['withholdStatus'] && $param['fundauthAmount'] == 0 ){
			$result['fundauthStatus'] = false;
			try{
				\App\Order\Modules\Repository\Pay\PayCreater::createWithhold($param);
			} catch (Exception $ex) {
				return false;
			}
		}
		//需要签约代扣+预授权金额不为0 【创建签约代扣+预授权的支付单】
		elseif( $result['withholdStatus'] && $param['fundauthAmount'] != 0 ){
			$result['fundauthStatus'] = true;
			try{
				\App\Order\Modules\Repository\Pay\PayCreater::createWithholdFundauth($param);
			} catch (Exception $ex) {
				return false;
			}
		}
		//不需要签约代扣+预授权金额为0 【不创建支付单】
		elseif( !$result['withholdStatus'] && $param['fundauthAmount'] == 0 ){
            $result['fundauthStatus'] = false;
			$result['isPay'] = false;
		}
		//不需要签约代扣+预授权金额不为0 【创建预授权支付单】
		else{
			$result['fundauthStatus'] = true;
			try{
				\App\Order\Modules\Repository\Pay\PayCreater::createFundauth($param);
			} catch (Exception $ex) {
				return false;
			}
		}
		return $result;
	}
	/**
	 * 判断支付->预授权
	 * @param type $param
	 */
	public static function __paymentFundAuth($param) {
		//记录最终结果
		$result = [];
		//判断预授权
		//创建普通支付的支付单
		if( $param['fundauthAmount'] == 0 ){
			$result['fundauthStatus'] = false;
			try{
				\App\Order\Modules\Repository\Pay\PayCreater::createPayment($param);
			} catch (Exception $ex) {
				return false;
			}
		}
		//创建支付+预授权的支付单
		else{
			try{
				\App\Order\Modules\Repository\Pay\PayCreater::createPaymentFundauth($param);
			} catch (Exception $ex) {
				return false;
			}
			$result['fundauthStatus'] = true;
		}
		return $result;
	}


	/**
	 * 校验订单创建过程中 支付单创建需要的参数
	 * @param Array $param
	 */
	public static function __praseParam( &$param ) {
//		$paramArr = filter_array($param, [
//	 		'payType' => 'required',//支付方式 【必须】<br/>
//	 		'payChannelId' => 'required',//支付渠道 【必须】<br/>
//			'userId' => 'required',//业务用户ID<br/>
//			'businessType' => 'required',//业务类型<br/>
//			'businessNo' => 'required',//业务编号<br/>
//			'paymentAmount' => 'required',//Price 支付金额，单位：元<br/>
//			'fundauthAmount' => 'required',//Price 预授权金额，单位：元<br/>
//			'paymentFenqi' => 'required',//int 分期数，取值范围[0,3,6,12]，0：不分期<br/>
//		]);
//		if( count($paramArr) != 8 ){
//			return FALSE;
//		}
//		$param = $paramArr;
		return true;
	}
	

}