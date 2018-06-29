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
use App\Order\Modules\Repository\Pay\WithholdQuery;

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
     * 'pay_channel_id'=>,//支付渠道
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
            $orderCreater = new RiskComponnet($orderCreater,$data['user_id']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id'],$data['pay_channel_id']);

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
			
			
			//-+----------------------------------------------------------------
			// | 创建支付单
			//-+----------------------------------------------------------------
//			$payResult = self::__createPay([
//				'payType' => $data['pay_type'],//支付方式 【必须】<br/>
//				'payChannelId' => $data['pay_channel_id'],//支付渠道 【必须】<br/>
//				'userId' => $data['user_id'],//业务用户ID 【必须】<br/>
//				'businessType' => OrderStatus::BUSINESS_ZUJI,//业务类型（租机业务 ）【必须】<br/>
//				'businessNo' => $orderNo,//业务编号（订单编号）【必须】<br/>
//				'fundauthAmount' => $schemaData['order']['order_yajin'],//Price 预授权金额（押金），单位：元【必须】<br/>
//				'paymentAmount' => $schemaData['order']['order_zujin'],//Price 支付金额（总租金），单位：元【必须】<br/>
//				'paymentFenqi' => $schemaData['order']['order_fenqi'],//int 分期数，取值范围[0,3,6,12]，0：不分期【必须】<br/>
//			]);
            $payResult = self::__createPay([
                'payType' => $data['pay_type'],//支付方式 【必须】<br/>
                'payChannelId' => $data['pay_channel_id'],//支付渠道 【必须】<br/>
                'userId' => $data['user_id'],//业务用户ID 【必须】<br/>
                'businessType' => OrderStatus::BUSINESS_ZUJI,//业务类型（租机业务 ）【必须】<br/>
                'businessNo' => $orderNo,//业务编号（订单编号）【必须】<br/>
                'fundauthAmount' => 0.01,//Price 预授权金额（押金），单位：元【必须】<br/>
                'paymentAmount' => 0.01,//Price 支付金额（总租金），单位：元【必须】<br/>
                'paymentFenqi' => 0,//int 分期数，取值范围[0,3,6,12]，0：不分期【必须】<br/>
            ]);
			//支付单创建错误，返回错误
			if( !$payResult ){
                DB::rollBack();
				return false;
			}
			//如果订单无需支付【不用创建支付单】则修改订单状态为无需支付
			if(!$payResult['isPay']){
				//修改订单状态为无需支付
                    $data['order_status']=OrderStatus::OrderPayed;
                    $b =Order::where('order_no', '=', $orderNo)->update($data);
                    if(!$b){
                        DB::rollBack();
                        return false;
                    }
			}
			$payResult = [
				'withholdStatus' => $payResult['withholdStatus'],
				'paymentStatus' => $payResult['paymentStatus'],
				'fundauthStatus' => $payResult['fundauthStatus'],
			];
			
            DB::commit();
//            $need_to_fundauth ="N";
//            if($data['pay_type'] == PayInc::WithhodingPay && $schemaData['order']['order_yajin']>0){
//                $need_to_fundauth ="Y";
//            }
            $result = [
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['credit'],
                'credit_status'		=> $b,
                //预授权金额
                'fundauth_amount'=>$schemaData['order']['order_yajin'],
                //支付方式
                'pay_type'=>$data['pay_type'],
                // 是否需要 签收代扣协议
//                'need_to_sign_withholding'	 => $schemaData['withholding']['needWithholding'],
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $schemaData['user']['certified']?'N':'Y',
//                //是否需要预授权
//                'need_to_fundauth'	 => $need_to_fundauth,

                '_order_info' => $schemaData,
                'order_no'=>$orderNo,

            ];
           // 创建订单后 发送支付短信。;
//            $orderNoticeObj = new OrderNotice(OrderStatus::BUSINESS_ZUJI,$orderNo,SceneConfig::ORDER_CREATE);
//            $orderNoticeObj->notify();
            //发送取消订单队列
        $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderCancel_".$orderNo,config("tripartite.ORDER_API"), [
            'method' => 'api.inner.miniCancelOrder',
            'order_no'=>$orderNo,
            'user_id'=>$data['user_id'],
            'time' => time(),
        ],time()+7200,"");
            OrderLogRepository::add($data['user_id'],$schemaData['user']['user_mobile'],\App\Lib\PublicInc::Type_User,$orderNo,"下单","用户下单");
			
            return array_merge($result,['pay_info'=>$payResult]);

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
            $orderCreater = new RiskComponnet($orderCreater,$data['user_id']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type'],$data['credit_amount']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id'],$data['pay_channel_id']);

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
            $b =JobQueueApi::addScheduleOnce(config('app.env')."OrderCancel_".$data['order_no'],config("tripartite.ORDER_API"), [
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
            $orderCreater = new RiskComponnet($orderCreater,$data['user_id']);

            //押金
            $orderCreater = new DepositComponnet($orderCreater,$data['pay_type']);

            //代扣
            $orderCreater = new WithholdingComponnet($orderCreater,$data['pay_type'],$data['user_id'],$data['pay_channel_id']);

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

//            $need_to_fundauth ="N";
//            if($data['pay_type'] == PayInc::WithhodingPay && $schemaData['order']['order_yajin']>0){
//                $need_to_fundauth ="Y";
//            }

            $result = [
                'coupon'         => $data['coupon'],
                'certified'			=> $schemaData['user']['certified']?'Y':'N',
                'certified_platform'=> Certification::getPlatformName($schemaData['user']['certified_platform']),
                'credit'			=> ''.$schemaData['user']['credit'],
                'credit_status'		=> $b,
                //预授权金额
                'fundauth_amount'=>$schemaData['order']['order_yajin'],
                //支付方式
                'pay_type'=>$data['pay_type'],
                // 是否需要 签收代扣协议
              //  'need_to_sign_withholding'	 => $schemaData['withholding']['needWithholding'],
                // 是否需要 信用认证
                'need_to_credit_certificate'			=> $schemaData['user']['certified']?'N':'Y',
                //是否需要预授权
            //    'need_to_fundauth'	 => $need_to_fundauth,
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
	
	/**
	 * 创建支付单
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
	private static function __createPay( $param ) {
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
		elseif( $param['payType'] = PayInc::FlowerStagePay || $param['payType'] = PayInc::UnionPay ){
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
	private static function __withholdFundAuth($param) {
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
	private static function __paymentFundAuth($param) {
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
	private static function __praseParam( &$param ) {
		$paramArr = filter_array($param, [
	 		'payType' => 'required',//支付方式 【必须】<br/>
	 		'payChannelId' => 'required',//支付渠道 【必须】<br/>
			'userId' => 'required',//业务用户ID<br/>
			'businessType' => 'required',//业务类型<br/>
			'businessNo' => 'required',//业务编号<br/>
			'paymentAmount' => 'required',//Price 支付金额，单位：元<br/>
			'fundauthAmount' => 'required',//Price 预授权金额，单位：元<br/>
			'paymentFenqi' => 'required',//int 分期数，取值范围[0,3,6,12]，0：不分期<br/>
		]);
		if( count($paramArr) != 8 ){
			return FALSE;
		}
		$param = $paramArr;
		return true;
	}
	

}