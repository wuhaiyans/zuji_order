<?php
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Lib\Common\JobQueueApi;
use App\Lib\Excel;
use App\Order\Modules\Inc\{OrderBuyoutStatus,OrderFreezeStatus,OrderStatus,OrderGoodStatus,PayInc};
use App\Order\Modules\OrderExcel\{CronCollection,CronOperator};
use App\Order\Modules\Repository\Order\{Goods,Order};
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\ShortMessage\{BuyoutConfirm,SceneConfig};
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderBuyout;
use App\Order\Modules\Repository\{OrderRepository,OrderGoodsRepository,OrderGoodsInstalmentRepository,OrderUserCertifiedRepository};
use Illuminate\Support\Facades\DB;
use App\Order\Modules\Repository\{Pay,OrderLogRepository,GoodsLogRepository};
use App\Order\Modules\Repository\Pay\{PayStatus,PaymentStatus,WithholdStatus,FundauthStatus,PayQuery};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayFactory;

/**
 * 支付控制器 
 * 
 * 
 */
class PayCenterController extends Controller
{
    
    public function __construct()
    {
        
    }
    
    /**
     * 支付入口
     * @access public
     * @author gaobo
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(Request $request)
    {
        //接收请求参数
        $request_params = $request->all();
        $to_business_params = $request_params['params'];
        //过滤参数

         $rule = [

            'business_type'=>'required',
            'business_no'=>'required',
            'pay_channel_id'=>'required',
            'callback_url'=>'required',
        ];
        $validator = app('validator')->make($to_business_params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        } 





        $userInfo = $request_params['userinfo'];
        //支付 扩展参数
        $ip = isset($userInfo['ip'])?$userInfo['ip']:'';
        //支付 扩展参数
        $extended_params = isset($to_business_params['extended_params'])?$to_business_params['extended_params']:[];
        // 微信支付，交易类型：JSAPI，redis读取openid
        if( $to_business_params['pay_channel_id'] == \App\Order\Modules\Repository\Pay\Channel::Wechat ){
            if( isset($extended_params['wechat_params']['trade_type']) && $extended_params['wechat_params']['trade_type']=='JSAPI' ){
                $_key = 'wechat_openid_'.$request_params['auth_token'];
                $openid = \Illuminate\Support\Facades\Redis::get($_key);
                if( $openid ){
                    $extended_params['wechat_params']['openid'] = $openid;
                }
            }
        }
        //业务工厂获取业务
        $business = \App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayFactory::getBusinessPay($to_business_params['business_type'], $to_business_params['business_no']);
        //获取业务详情
        $businessStatus = $business->getBusinessStatus();
        //校验业务状态是否有效
        if(!$businessStatus){
            return apiResponse([],ApiStatus::CODE_0,"该订单无需支付");
        }
        
        //获取支付单
        $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusinessTest($to_business_params['business_type'], $to_business_params['business_no']);
        //检测支付单状态
        if($pay){
            if($pay->getStatus() == PayStatus::UNKNOWN){
                return apiResponse([],ApiStatus::CODE_90000,"该订单支付单无效");
            }
            if($pay->getStatus() == PayStatus::SUCCESS){
                return apiResponse([],ApiStatus::CODE_90004,"该订单支付已完成");
            }
            if($pay->getStatus() == PayStatus::CLOSED){
                return apiResponse([],ApiStatus::CODE_90005,"该订单支付已关闭");
            }
            
            //对比直付本地数据
            $paymentInfo = $business->getPaymentInfo();
            if($paymentInfo->getPaymentAmount() != $pay->getPaymentAmount()){
                $pay->setPaymentAmount($paymentInfo->getPaymentAmount());
                //更新支付单
                $pay->update();
            }
            
            //组装url参数
            $currenturl_params = [
                'name'            => $business->getPayName(),
                'front_url'       => $to_business_params['callback_url'],
                'business_no'     => $to_business_params['business_no'],
                'ip'              => $ip,
                'extended_params' => $extended_params,// 扩展参数
            ];
            
            $paymentUrl = $pay->getCurrentUrl($to_business_params['pay_channel_id'],$currenturl_params);
            $business->addLog($userInfo);
            return apiResponse($paymentUrl,ApiStatus::CODE_0);
        }else{
            //创建支付
            $create_center = new \App\Order\Modules\Repository\Pay\PayCreateCenter();
            //设置基础参数
            $create_center->setUserId($business->getUserId());
            $create_center->setBusinessNo($to_business_params['business_no']);
            $create_center->setBusinessType($to_business_params['business_type']);
            
            //直付
            $paymentInfo = $business->getPaymentInfo();
            if($paymentInfo->getNeedPayment()){
                $create_center->setPaymentAmount($paymentInfo->getPaymentAmount());
                $create_center->setPaymentFenqi($paymentInfo->getPaymentFenqi());
                $create_center->setTrade($paymentInfo->getTrate());
                $create_center->setPaymentNo(\creage_payment_no());
                $create_center->setStatus(PayStatus::WAIT_PAYMENT);
                $create_center->setPaymentStatus(PaymentStatus::WAIT_PAYMENT);
                $create_center->setGoingOnPay(true);
            }
            
            //预授权
            $fundauthInfo = $business->getFundauthInfo();
            if($fundauthInfo->getNeedFundauth()){
                $create_center->setFundauthAmount($fundauthInfo->getFundauthAmount());
                $create_center->setTrade($fundauthInfo->getTrate());
                $create_center->setFundauthNo(\creage_fundauth_no());
                $create_center->setStatus(PayStatus::WAIT_FUNDAUTH);
                $create_center->setFundauthStatus(FundauthStatus::WAIT_FUNDAUTH);
                $create_center->setGoingOnPay(true);
            }
            
            //签约代扣
            $withholdInfo = $business->getWithHoldInfo();
            if($withholdInfo->getNeedWithhold()){
                $create_center->setTrade($withholdInfo->getTrate());
                $create_center->setWithhold_no(\creage_withhold_no());
                $create_center->setStatus(PayStatus::WAIT_WHITHHOLD);
                $create_center->setWithholdStatus(WithholdStatus::WAIT_WITHHOLD);
                $create_center->setGoingOnPay(true);
            }
            
            //如果支付方式存在
            if($create_center->getGoingOnPay()){
                $create_center->create();
                $pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(OrderStatus::BUSINESS_BUYOUT, $to_business_params['business_no']);
                //组装url参数
                $currenturl_params = [
                    'name'            => $business->getPayName(),
                    'front_url'       => $to_business_params['callback_url'],
                    'business_no'     => $to_business_params['business_no'],
                    'ip'              => $ip,
                    'extended_params' => $extended_params,// 扩展参数
                ];
                $paymentUrl = $pay->getCurrentUrl($to_business_params['pay_channel_id'],$currenturl_params);
                $business->addLog($userInfo);
                return apiResponse($paymentUrl,ApiStatus::CODE_0);
            }
            //空请求
            return apiResponse('没有支付方式',ApiStatus::CODE_10100);
        }
    }
    
}