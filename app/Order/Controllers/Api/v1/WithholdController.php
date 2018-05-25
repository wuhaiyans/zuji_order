<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\Payment\CommonPaymentApi;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\OrderPayWithholdStatus;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderInstalment;
use App\Order\Modules\Service\OrderPayWithhold;
use App\Order\Modules\Repository\OrderRepository;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Lib\Common\LogApi;

class WithholdController extends Controller
{

    /**
     *  签约代扣接口
     * @request Array
     * [
     *      'front_url'         => '' 【必须】String 前端回跳地址
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'url' => '',//签约跳转url地址
     * ]
     */
    public function sign(Request $request){
        $params     = $request->all();
        $appid      = $request['appid'];
        // 参数过滤
        $rules = [
            'front_url'         => 'required',  //前端跳转地址
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        // 获取渠道ID
        $ChannelInfo = \App\Lib\Channel\Channel::getChannel(config('tripartite.Interior_Goods_Request_data'),$appid);
        if (!is_array($ChannelInfo)) {
            return apiResponse([], ApiStatus::CODE_10102, "channel_id 错误");
        }
        $channelId = intval($ChannelInfo['_channel']['id']);


        // 创建第支付方式
        $data = [
            'businessType'  => \App\Order\Modules\Inc\OrderStatus::OrderWaitPaying, //暂留
            'businessNo'    => createNo(),
        ];
        $payment = \App\Order\Modules\Repository\Pay\PayCreater::createWithhold($data);


        // 获取URL地址
        $subject = "签署代扣协议";
        $urlData = [
            'name'			=> $subject,	            // 交易名称
	 		'front_url'		=> $params['front_url'],	// 前端回跳地址
        ];
        $url = $payment->getCurrentUrl($channelId,$urlData);

        if(!$url){
            return apiResponse([], ApiStatus::CODE_71008, "获取签约代扣URL地址失败");
        }

        return apiResponse(['url'=>$url['withholding_url']],ApiStatus::CODE_0,"success");
    }


    /*
     * 代扣协议查询
     * @param array $request
	 * [
	 *		'user_id'		=> '', //【必选】int 用户ID
	 * ]
	 * @return array
	 * [
	 *		'agreement_no'		=> '', //【必选】string 支付系统签约编号
	 *		'out_agreement_no'	=> '', //【必选】string 业务系统签约编号
	 *		'status'			=> '', //【必选】string 状态；init：初始化；signed：已签约；unsigned：已解约
	 *		'create_time'		=> '', //【必选】int	创建时间
	 *		'sign_time'			=> '', //【必选】int 签约时间
	 *		'unsign_time'		=> '', //【必选】int 解约时间
	 *		'user_id'			=> '', //【必选】int 用户ID
	 * ]
     */
    public function query(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        if(!$appid){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $userId = $params['user_id'];
        if(!$userId){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $payWithhold = \App\Order\Modules\Repository\OrderPayWithholdRepository::find($userId);
        if(empty($payWithhold)){
            return apiResponse([],ApiStatus::CODE_20001, "参数错误");
        }

        $data = [
            'agreement_no'		=> $payWithhold['out_withhold_no'], //【必选】string 支付系统签约编号
            'out_agreement_no'	=> $payWithhold['withhold_no'], //【必选】string 业务系统签约编号
	 		'user_id'			=> $userId, //【必选】string 业务系统用户ID
        ];
        $withholdInfo = \App\Lib\Payment\CommonWithholdingApi::queryAgreement($data);
        if(!$withholdInfo){
            return apiResponse([],ApiStatus::CODE_50000, "查询协议错误");
        }

        return apiResponse($withholdInfo['data'],ApiStatus::CODE_0);
    }



    /**
     * 解约代扣
     * $request Array
     * [
     *      'user_id' => '1', // 用户ID
     * ]
     * returnn string
     */
    public function unsign(Request $request){
        $params     = $request->all();
        $appid      = $request['appid'];
        // 参数过滤
        $rules = [
            'user_id'         => 'required|int',  //前端跳转地址
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        //开启事务
        DB::beginTransaction();

        $userId     = $params['user_id'];
        // 查询用户协议
        $withholdInfo = OrderPayWithhold::find($userId);
        if( !$withholdInfo ){
            DB::rollBack();
            Log::error("[代扣解约]查询用户信息失败");
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        if( !$withholdInfo['withhold_status'] == OrderPayWithholdStatus::UNSIGN ){
            DB::rollBack();
            Log::error("用户未签约该协议");
            return apiResponse( [], ApiStatus::CODE_71004, '用户未签约该协议');
        }

        if( !$withholdInfo['out_withhold_no'] ){
            DB::rollBack();
            Log::error("获取用户支付宝id失败");
            return apiResponse( [], ApiStatus::CODE_71004, '获取支付系统代扣协议码失败');
        }

        if( !$withholdInfo['withhold_no'] ){
            DB::rollBack();
            Log::error("获取用户代扣协议码失败");
            return apiResponse( [], ApiStatus::CODE_71004, '获取用户代扣协议码失败');
        }

        // 查看用户是否有未扣款的分期
        /* 如果有未扣款的分期信息，则不允许解约 */
        $n = \App\Order\Models\OrderInstalment::query()->where([
            'user_id'=> $userId])
            ->whereIn('status', [OrderInstalmentStatus::UNPAID,OrderInstalmentStatus::FAIL]
        )->get()->count();

        if( $n > 0 ){
            Log::error("[代扣解约]订单分期查询错误");
            return apiResponse( [], ApiStatus::CODE_71010, '解约失败，有未完成分期');
        }
        try {

            $data = [
                'user_id'           => $userId, //租机平台用户ID
                'agreement_no'      => $withholdInfo['out_withhold_no'], //支付平台签约协议号
                'out_agreement_no'  => $withholdInfo['withhold_no'],    //业务平台签约协议号
                'back_url'          => env("API_INNER_URL") . "/unSignNotify", //回调地址
            ];

            $b = \App\Lib\Payment\CommonWithholdingApi::unSign( $data );
            if( !$b ){
                DB::rollBack();
                Log::error("[代扣解约]调用支付宝解约接口失败");
                return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
            }

            // 成功
            DB::commit();
            return apiResponse([], ApiStatus::CODE_0, "success");
        } catch (\Exception $exc) {
            return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');

        }
    }

    /**
     * 代扣 扣款接口
     * @$request array
     * [
     *      'instalment_id' => '', //分期表自增id
     *      'remark'        => '', //备注信息
     * ]
     */
    public function createpay(Request $request){
        $params     = $request->all();

        $rules = [
            'instalment_id'     => 'required|int',
            'remark'            => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        $instalmentId   = $params['instalment_id'];
        $remark         = $params['remark'];

        //开启事务
        DB::beginTransaction();

        // 查询分期信息
        $instalmentInfo = OrderInstalment::queryByInstalmentId($instalmentId);

        if( !is_array($instalmentInfo)){
            DB::rollBack();
            // 提交事务
            return apiResponse([], $instalmentInfo, ApiStatus::$errCodes[$instalmentInfo]);
        }

        $allow = OrderInstalment::allowWithhold($instalmentId);
        if(!$allow){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71000, "不允许扣款" );
        }

        // 生成交易码
        $tradeNo = createNo();

        // 状态在支付中或已支付时，直接返回成功
        if( $instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS && $instalmentInfo['status'] = OrderInstalmentStatus::PAYING ){
            return apiResponse($instalmentInfo,ApiStatus::CODE_0,"success");
        }

        // 扣款交易码
        if( $instalmentInfo['trade_no']=='' ){
            // 1)记录租机交易码
            $b = OrderInstalment::set_trade_no($instalmentId, $tradeNo);
            if( $b === false ){
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_71002, "租机交易码错误");
            }
            $instalmentInfo['trade_no'] = $tradeNo;
        }
        $tradeNo = $instalmentInfo['trade_no'];

        // 订单
        //查询订单记录
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }

        // 查询用户协议
        $withholdInfo = OrderPayWithhold::find($orderInfo['user_id']);
        if(empty($withholdInfo)){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71004, ApiStatus::$errCodes[ApiStatus::CODE_71004]);
        }

        $userInfo = \App\Lib\User\User::getUser(config('tripartite.Interior_Goods_Request_data'), $orderInfo['user_id']);
        if( !is_array($userInfo )){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_60000, "获取用户接口失败");
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderInstalment::save(['id'=>$instalmentId],$data);
        if(!$result){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71001, '扣款备注保存失败');
        }
        // 商品
        $subject = '订单-'.$instalmentInfo['order_no'].'-'.$instalmentInfo['goods_no'].'-第'.$instalmentInfo['times'].'期扣款';

        // 价格
        $amount = $instalmentInfo['amount'] * 100;
        if( $amount<0 ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71003, '扣款金额不能小于1分');
        }

        $orderGoods = New \App\Order\Modules\Service\OrderGoods();
        $goodsInfo  = $orderGoods->getGoodsInfo($instalmentInfo['goods_no']);
        if(!$goodsInfo){
            return false;
        }

        //扣款要发送的短信
        $data_sms =[
            'mobile'        => $userInfo['mobile'],
            'orderNo'       => $orderInfo['order_no'],
            'realName'      => $userInfo['realname'],
            'goodsName'     => $goodsInfo['goods_name'],
            'zuJin'         => $amount,
        ];

        //判断支付方式
        if( $orderInfo['pay_type'] == PayInc::MiniAlipay ){
//            $this->zhima_order_confrimed_table =$this->load->table('order2/zhima_order_confirmed');
//            //获取订单的芝麻订单编号
//            $zhima_order_info = $this->zhima_order_confrimed_table->where(['order_no'=>$order_info['order_no']])->find(['lock'=>true]);
//            if(!$zhima_order_info){
//                $this->order_service->rollback();
//                showmessage('该订单没有芝麻订单号！','null',0);
//            }
//            //芝麻小程序下单渠道
//            $Withhold = new \zhima\Withhold();
//            $params['out_order_no'] = $order_info['order_no'];
//            $params['zm_order_no'] = $zhima_order_info['zm_order_no'];
//            $params['out_trans_no'] = $trade_no;
//            $params['pay_amount'] = $amount;
//            $params['remark'] = $remark;
//            $b = $Withhold->withhold( $params );
//            \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求",$params);
//            //判断请求发送是否成功
//            if($b == 'PAY_SUCCESS'){
//                // 提交事务
//                $this->order_service->commit();
//                \zuji\debug\Debug::error(Location::L_Trade,"小程序退款请求回执",$b);
//                showmessage('小程序扣款操作成功','null',1);
//            }elseif($b =='PAY_FAILED'){
//                $this->order_service->rollback();
//                $this->instalment_failed($instalment_info['fail_num'],$instalment_id,$instalment_info['term'],$data_sms);
//                showmessage("小程序支付失败", 'null');
//
//            }elseif($b == 'PAY_INPROGRESS'){
//                $this->order_service->commit();
//                showmessage("小程序支付处理中请等待", 'null');
//            }else{
//                $this->order_service->rollback();
//                showmessage("小程序支付处理失败", 'null');
//            }


        }else {

            // 支付宝用户的user_id
            $alipayUserId = $withholdInfo['out_withhold_no'];
            if (!$alipayUserId) {
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_71009, '支付宝用户的user_id错误');
            }

            // 代扣协议编号
            $agreementNo = $withholdInfo['withhold_no'];
            if (!$agreementNo) {
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_71004, '用户代扣协议编号错误');
            }
            // 代扣接口
            $withholding = new \App\Lib\Payment\CommonWithholdingApi;

            $backUrl = env("API_INNER_URL") . "/createpayNotify";

            $withholding_data = [
                'out_trade_no'  => $agreementNo,         //业务系统授权码
                'amount'        => $amount,              //交易金额；单位：分
                'back_url'      => $backUrl,             //后台通知地址
                'name'          => $subject,             //交易备注
                'agreement_no'  => $alipayUserId,        //支付平台代扣协议号
                'user_id'       => $orderInfo['user_id'],//业务平台用户id
            ];
            $withholding_b = $withholding->deduct($withholding_data);
            if (!$withholding_b) {
                DB::rollBack();
                \App\Lib\Common\LogApi::error('分期代扣错误', $withholding_data);
                if (get_error() == "BUYER_BALANCE_NOT_ENOUGH" || get_error() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $data_sms);
                    return apiResponse([], ApiStatus::CODE_71004, '买家余额不足');
                } else {
                    return apiResponse([], ApiStatus::CODE_71006, '扣款失败');
                }
            }

            //发送短信
            SmsApi::sendMessage($data_sms['mobile'], 'hsb_sms_b427f', $data_sms);

            //发送消息通知
            //通过用户id查询支付宝用户id

//            $MessageSingleSendWord = new \alipay\MessageSingleSendWord($alipayUserId);
//            //查询账单
//            $year = substr($instalment_info['term'], 0, 4);
//            $month = substr($instalment_info['term'], -2);
//            $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
//            $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
//            $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
//            $messageArr = [
//                'amount' => $amount,
//                'bill_type' => '租金',
//                'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
//                'pay_time' => date('Y-m-d H:i:s'),
//            ];
//            $b = $MessageSingleSendWord->PaySuccess($messageArr);
//            if ($b === false) {
//                \zuji\debug\Debug::error(Location::L_Trade, '发送消息通知PaySuccess', $MessageSingleSendWord->getError());
//                return;
//            }

            // 提交事务
            DB::commit();
            return apiResponse([],ApiStatus::CODE_0,"success");
        }

    }

    /**
     * 多项扣款
     * @$request array
     * [
     *      'ids' => [], 【必须】分期表自增id数组
     * ]
     * return String
     */
    public function multi_createpay(Request $request)
    {
        ini_set('max_execution_time', '0');

        $params     = $request->all();

        $rules = [
            'ids'            => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        $ids = $params['ids'];
        if(!is_array($ids) && empty($ids)){
            return apiResponse([], ApiStatus::CODE_71006, "扣款失败");
        }

        foreach ($ids as $instalmentId) {

            if ($instalmentId < 1) {
                Log::error("参数错误");
                continue;
            }
            $remark = "代扣多项扣款";


            $instalmentInfo = OrderInstalment::queryByInstalmentId($instalmentId);
            if (!is_array($instalmentInfo)) {
                Log::error("分期信息查询失败");
                continue;
            }

            $allow = OrderInstalment::allowWithhold($instalmentId);
            if (!$allow) {
                Log::error("不允许扣款");
                continue;
            }

            // 生成交易码
            $tradeNo = createNo();

            //开启事务
            DB::beginTransaction();

            // 状态在支付中或已支付时，直接返回成功
            if ($instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS && $instalmentInfo['status'] = OrderInstalmentStatus::PAYING) {
                continue;
            }

            // 扣款交易码
            if ($instalmentInfo['trade_no'] == '') {
                // 1)记录租机交易码
                $b = OrderInstalment::set_trade_no($instalmentId, $tradeNo);
                if ($b === false) {
                    DB::rollBack();
                    Log::error("租机交易码错误");
                    continue;
                }
                $instalmentInfo['trade_no'] = $tradeNo;
            }
            $tradeNo = $instalmentInfo['trade_no'];

// 订单
            //查询订单记录
            $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
            if (!$orderInfo) {
                DB::rollBack();
                Log::error("数据异常");
                continue;
            }

            // 查询用户协议
            $withholdInfo = OrderPayWithhold::find($orderInfo['user_id']);
            if(empty($withholdInfo)){
                DB::rollBack();
                continue;
            }

            $userInfo = \App\Lib\User\User::getUser(config('tripartite.Interior_Goods_Request_data'), $orderInfo['user_id']);
            if (!is_array($userInfo)) {
                DB::rollBack();
                Log::error("用户信息错误");
                continue;
            }

            // 保存 备注，更新状态
            $data = [
                'remark' => $remark,
                'status' => OrderInstalmentStatus::PAYING,// 扣款中
            ];
            $result = OrderInstalment::save(['id' => $instalmentId], $data);
            if (!$result) {
                DB::rollBack();
                Log::error("扣款备注保存失败");
                continue;
            }
            // 商品
            $subject = '订单-' . $instalmentInfo['order_no'] . '-' . $instalmentInfo['goods_no'] . '-第' . $instalmentInfo['times'] . '期扣款';

            // 价格
            $amount = $instalmentInfo['amount'] * 100;
            if ($amount < 0) {
                DB::rollBack();
                Log::error("扣款金额不能小于1分");
                continue;
            }

            $orderGoods = New \App\Order\Modules\Service\OrderGoods();
            $goodsInfo  = $orderGoods->getGoodsInfo($instalmentInfo['goods_no']);
            if(!$goodsInfo){
                return false;
            }

            //扣款要发送的短信
            $dataSms = [
                'mobile' => $userInfo['mobile'],
                'orderNo' => $orderInfo['order_no'],
                'realName' => $userInfo['realname'],
                'goodsName' => $goodsInfo['goods_name'],
                'zuJin' => $amount,
            ];

            //判断支付方式
            if ($orderInfo['pay_type'] == PayInc::MiniAlipay) {

            } else {

                // 支付宝用户的user_id
                $alipayUserId = $withholdInfo['out_withhold_no'];
                if (!$alipayUserId) {
                    DB::rollBack();
                    Log::error("支付宝用户的user_id错误");
                    continue;
                }

                // 代扣协议编号
                $agreementNo = $withholdInfo['withhold_no'];
                if (!$agreementNo) {
                    DB::rollBack();
                    Log::error("用户代扣协议编号错误");
                    continue;
                }
                // 代扣接口
                $withholding = new \App\Lib\Payment\CommonWithholdingApi;

                $backUrl = env("API_INNER_URL") . "/createpayNotify";

                $withholding_data = [
                    'out_trade_no'  => $agreementNo,         //业务系统授权码
                    'amount'        => $amount,              //交易金额；单位：分
                    'back_url'      => $backUrl,             //后台通知地址
                    'name'          => $subject,             //交易备注
                    'agreement_no'  => $alipayUserId,        //支付平台代扣协议号
                    'user_id'       => $orderInfo['user_id'],//业务平台用户id
                ];
                $withholding_b = $withholding->deduct($withholding_data);

                if (!$withholding_b) {
                    \App\Lib\Common\LogApi::error('分期代扣错误', $withholding_data);
                    //修改扣款失败
                    OrderInstalment::save(['id'=>$instalmentId],['status'=>OrderInstalmentStatus::FAIL]);
                    if (get_error() == "BUYER_BALANCE_NOT_ENOUGH" || get_error() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                        OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                        Log::error("买家余额不足");
                        continue;
                    }
                }

                //发送短信
                SmsApi::sendMessage($dataSms['mobile'], 'hsb_sms_b427f', $dataSms);

                //发送消息通知
                //通过用户id查询支付宝用户id

//            $MessageSingleSendWord = new \alipay\MessageSingleSendWord($alipay_user_id);
//            //查询账单
//            $year = substr($instalment_info['term'], 0, 4);
//            $month = substr($instalment_info['term'], -2);
//            $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
//            $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
//            $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
//            $message_arr = [
//                'amount' => $amount,
//                'bill_type' => '租金',
//                'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
//                'pay_time' => date('Y-m-d H:i:s'),
//            ];
//            $b = $MessageSingleSendWord->PaySuccess($message_arr);
//            if ($b === false) {
//                \zuji\debug\Debug::error(Location::L_Trade, '发送消息通知PaySuccess', $MessageSingleSendWord->getError());
//                return;
//            }
            }
            // 提交事务
            DB::commit();
        }
        return apiResponse([], ApiStatus::CODE_0, "success");

    }


    /**
     * 主动还款
     * @requwet Array
     * [
     *      'return_url'         => '', 【必须】 String 前端回调地址
     *      'instalment_id'      => '', 【必须】 Int    分期主键ID
     * ]
     * @return String url 前端支付URL
     */
    public function repayment(Request $request){
        $params     = $request->all();
        $appid      = $request['appid'];
        $rules = [
            'return_url'        => 'required',
            'instalment_id'     => 'required|int',
        ];

        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([], $validateParams['code']);
        }
        $params = $params['params'];

        // 订单详情
        $orderInfo  = OrderRepository::getOrderInfo($params);
        if(!$orderInfo){
            return apiResponse([], ApiStatus::CODE_20001, "order_no不能为空");
        }

        if($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService && $orderInfo['freeze_type'] != \App\Order\Modules\Inc\OrderFreezeStatus::Non){
            return apiResponse([], ApiStatus::CODE_71000, "该订单不在服务中 不允许提前还款");
        }

        // 查询分期
        if(empty($params['nologin'])){
            $instal_where = ['id'=>$params['instalment_id']];
        }else{
            $instal_where = [
                'order_no'  => $params['order_no'],
                'term'      => date('Ym'),
            ];
        }
        $instalmentInfo  = OrderInstalment::queryInfo($instal_where);
        if(!$instalmentInfo){
            return apiResponse([], ApiStatus::CODE_20001, "查询分期数据错误");
        }

        if( $instalmentInfo['status'] != OrderInstalmentStatus::UNPAID && $instalmentInfo['status'] != OrderInstalmentStatus::FAIL){
            return apiResponse([], ApiStatus::CODE_71000, "该分期不允许提前还款");
        }

        // 代扣协议
        $payWithhold = \App\Order\Modules\Repository\OrderPayWithholdRepository::find($orderInfo['user_id']);
        if(empty($payWithhold)){
            return apiResponse([],ApiStatus::CODE_20001, "参数错误");
        }

        // 渠道
        $ChannelInfo = \App\Lib\Channel\Channel::getChannel(config('tripartite.Interior_Goods_Request_data'),$appid);
        if (!is_array($ChannelInfo)) {
            return apiResponse([], ApiStatus::CODE_10102, "channel_id 错误");
        }
        $channelId = intval($ChannelInfo['_channel']['id']);

        // 优惠券判断 (暂留)
        $amount = $instalmentInfo['amount'] * 100;

        // 回调地址
        $backUrl = env("API_INNER_URL") . "/repaymentNotify";

        $payData = [
            'out_payment_no'	=> $payWithhold['withhold_no'],	//【必选】string    业务支付唯一编号
	 		'payment_amount'	=> $amount,	                    //【必选】int       交易金额；单位：分
	 		'payment_fenqi'		=> $instalmentInfo['times'],	//【必选】int       分期数
	 		'channel_type'	    => $channelId,	                //【必选】int       支付渠道
	 		'name'			    => '主动还款',	                //【必选】string    交易名称
	 		'back_url'		    => $backUrl,	                //【必选】string    后台通知地址
	 		'front_url'		    => $params['return_url'],	    //【必选】string    前端回跳地址
	 		'user_id'		    => $orderInfo['user_id'],	    //【可选】int       业务平台yonghID
        ];

        // 创建支付单
        $url = CommonPaymentApi::pageUrl($payData);
        if(!$url){
            return apiResponse([],ApiStatus::CODE_30900, "创建支付单失败");
        }

        return apiResponse(['url'=>$url['payment_url']],ApiStatus::CODE_0);


    }

    /**
     * 分期扣款异步回调处理
     * @requwet Array
     * [
     *      'reason'            => '', 【必须】 String 错误原因
     *      'status'            => '', 【必须】 int：success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
     *      'agreement_no'      => '', 【必须】 String 支付平台签约协议号
     *      'out_agreement_no'  => '', 【必须】 String 业务系统签约协议号
     *      'trade_no'          => '', 【必须】 String 支付平台交易码
     *      'out_trade_no'      => '', 【必须】 String 业务平台交易码
     * ]
     * @return String FAIL：失败  SUCCESS：成功
     */
    public function createpayNotify(Request $request){
        $params     = $request->all();

        $rules = [
            'reason'            => 'required',
            'status'            => 'required|int',
            'agreement_no'      => 'required',
            'out_agreement_no'  => 'required',
            'trade_no'          => 'required',
            'out_trade_no'      => 'required',
        ];

        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        // 扣款成功 修改分期状态
        $params = $params['params'];
        if($params['status'] == "success"){
            $trade_no = $params['out_trade_no'];
            //修改分期状态
            $b = OrderInstalment::save(['trade_no'=>$trade_no],['status'=>OrderInstalmentStatus::SUCCESS]);
            if(!$b){
                echo "FAIL";exit;
            }
        }
        echo "SUCCESS";
    }

    /**
     * 主动还款回调
     * @requwet Array
     * [
     *      'reason'            => '', 【必须】 String 错误原因
     *      'status'            => '', 【必须】 int：success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
     *      'payment_no'        => '', 【必须】 String 支付平台支付码
     *      'out_no'            => '', 【必须】 String 订单平台支付码
     * ]
     * @return String FAIL：失败  SUCCESS：成功
     */
    public function repaymentNotify(Request $request){
        $params     = $request->all();

        $rules = [
            'payment_no'  => 'required',
            'out_no'      => 'required',
            'status'      => 'required',
            'reason'      => 'required',
        ];
        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        // 扣款成功 修改分期状态
        $params = $params['params'];

        if($params['status'] == "success"){
            $trade_no = $params['out_no'];
            //修改分期状态
            $b = OrderInstalment::save(['trade_no'=>$trade_no],['status'=>OrderInstalmentStatus::SUCCESS]);
            if(!$b){
                echo "FAIL";exit;
            }
        }else{
            LogApi::info('支付异步通知', $params);
        }

        echo "SUCCESS";

    }

    /**
     * 代扣解约接口
     * @requwet Array
     * [
     *      'reason'            => '', 【必须】 String 错误原因
     *      'status'            => '', 【必须】 int：success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
     *      'agreement_no'      => '', 【必须】 String 支付平台签约协议号
     *      'out_agreement_no'  => '', 【必须】 String 业务系统签约协议号
     *      'user_id'           => '', 【必须】 int 用户ID
     * ]
     * @return String FAIL：失败  SUCCESS：成功
     */
    public function unSignNotify(Request $request){
        $params     = $request->all();

        $rules = [
            'reason'            => 'required',
            'status'            => 'required',
            'agreement_no'      => 'required',
            'out_agreement_no'  => 'required',
            'user_id'           => 'required',
        ];
        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        // 解约成功 修改协议表
        $params = $params['params'];

        if($params['status'] == "success"){
            $userId = $params['user_id'];
            // 解除代扣协议
            $b = \App\Order\Modules\Service\OrderPayWithhold::unsign_withhold($userId);
            if(!$b){
                echo "FAIL";exit;
            }

        }

        echo "SUCCESS";
    }




}
