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
        try{
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

        }catch(\Exception $exc){
            return apiResponse([],ApiStatus::CODE_50000);
        }

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
        $userId     = $params['params']['user_id'];

        // 查询用户协议
        $withholdInfo = OrderPayWithhold::find($userId);
        if( !$withholdInfo ){
            Log::error("[代扣解约]查询用户信息失败");
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        if( !$withholdInfo['withhold_status'] == OrderPayWithholdStatus::UNSIGN ){
            Log::error("用户未签约该协议");
            return apiResponse( [], ApiStatus::CODE_71004, '用户未签约该协议');
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
                Log::error("[代扣解约]调用支付宝解约接口失败");
                return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
            }

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

        $userInfo = \App\Lib\User\User::getUser($orderInfo['user_id']);
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
            return apiResponse([], ApiStatus::CODE_71001, '产品信息错误');
        }

        //扣款要发送的短信
        $dataSms =[
            'mobile'        => $userInfo['mobile'],
            'orderNo'       => $orderInfo['order_no'],
            'realName'      => $userInfo['realname'],
            'goodsName'     => $goodsInfo['goods_name'],
            'zuJin'         => $amount,
        ];

        //判断支付方式
        if( $orderInfo['pay_type'] == PayInc::MiniAlipay ){
            //获取订单的芝麻订单编号
            $miniOrderInfo = \App\Order\Modules\Repository\MiniOrderRentNotifyRepository::getMiniOrderRentNotify( $instalmentInfo['order_no'] );
            if( empty($miniOrderInfo) ){
                \App\Lib\Common\LogApi::info('本地小程序确认订单回调记录查询失败',$orderInfo['order_no']);
                return apiResponse([],ApiStatus::CODE_35003,'本地小程序确认订单回调记录查询失败');
            }
            //芝麻小程序扣款请求
            $miniParams['out_order_no']     = $miniOrderInfo['out_order_no'];
            $miniParams['zm_order_no']      = $miniOrderInfo['zm_order_no'];
            //扣款交易号
            $miniParams['out_trans_no']     = $tradeNo;
            $miniParams['pay_amount']       = $amount;
            $miniParams['remark']           = $subject;
            $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );
            //判断请求发送是否成功
            if($pay_status == 'PAY_SUCCESS'){
                return apiResponse([], ApiStatus::CODE_0, '小程序扣款操作成功');
            }elseif($pay_status =='PAY_FAILED'){
                OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                return apiResponse([], ApiStatus::CODE_35006, '小程序扣款请求失败');
            }elseif($pay_status == 'PAY_INPROGRESS'){
                return apiResponse([], ApiStatus::CODE_35007, '小程序扣款处理中请等待');
            }else{
                return apiResponse([], ApiStatus::CODE_50000, '小程序扣款处理失败（内部失败）');
            }
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
                'out_trade_no'  => $agreementNo,        //业务系统授权码
                'amount'        => $amount,              //交易金额；单位：分
                'back_url'      => $backUrl,             //后台通知地址
                'name'          => $subject,             //交易备注
                'agreement_no'  => $alipayUserId,         //支付平台代扣协议号
                'user_id'       => $orderInfo['user_id'],//业务平台用户id
            ];

            try{
                // 请求代扣接口
                $result = $withholding->deduct($withholding_data);

            }catch(\Exception $exc){
                DB::rollBack();
                v($exc,1);
                p($withholding_data,1);
                p($exc->getMessage());
                \App\Lib\Common\LogApi::error('分期代扣错误', $withholding_data);
                //捕获异常 买家余额不足
                if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                    return apiResponse([], ApiStatus::CODE_71004, '买家余额不足');
                } else {
                    return apiResponse([], ApiStatus::CODE_71006, '扣款失败');
                }

            }


            //发送短信
            $business_type = \App\Order\Modules\Repository\ShortMessage\Config::CHANNELID_OFFICAL;
            $orderNoticeObj  = new \App\Order\Modules\Service\OrderNotice($business_type, "", "InstalmentWithhold");
            $orderNoticeObj->notify($dataSms);

            //发送消息通知
            //通过用户id查询支付宝用户id
            $MessageSingleSendWord = new \App\Lib\AlipaySdk\sdk\MessageSingleSendWord($alipayUserId);
            //查询账单
            $year = substr($instalmentInfo['term'], 0, 4);
            $month = substr($instalmentInfo['term'], -2);
            $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
            $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
            $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
            $messageArr = [
                'amount' => $amount,
                'bill_type' => '租金',
                'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
                'pay_time' => date('Y-m-d H:i:s'),
            ];
            $b = $MessageSingleSendWord->PaySuccess($messageArr);
            if ($b === false) {
                Log::error("发送消息通知错误-" . $MessageSingleSendWord->getError());
            }
        }
        // 提交事务
        DB::commit();
        return apiResponse([],ApiStatus::CODE_0,"success");
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

            $userInfo = \App\Lib\User\User::getUser($orderInfo['user_id']);
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
                Log::error("产品信息错误");
                continue;
            }

            //扣款要发送的短信
            $dataSms = [
                'mobile' => $userInfo['mobile'],
                'orderNo' => $orderInfo['order_no'],
                'realName' => $userInfo['realname'],
                'goodsName' => $goodsInfo['goods_name'],
                'zuJin' => $amount,
            ];

            //判断支付方式 小程序
            if ($orderInfo['pay_type'] == PayInc::MiniAlipay) {
                //获取订单的芝麻订单编号
                $miniOrderInfo = \App\Order\Modules\Repository\MiniOrderRentNotifyRepository::getMiniOrderRentNotify( $instalmentInfo['order_no'] );
                if( empty($miniOrderInfo) ){
                    \App\Lib\Common\LogApi::info('本地小程序确认订单回调记录查询失败',$orderInfo['order_no']);
                    continue;
                }
                //芝麻小程序扣款请求
                $miniParams['out_order_no']     = $miniOrderInfo['out_order_no'];
                $miniParams['zm_order_no']      = $miniOrderInfo['zm_order_no'];
                //扣款交易号
                $miniParams['out_trans_no']     = $tradeNo;
                $miniParams['pay_amount']       = $amount;
                $miniParams['remark']           = $subject;
                $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );

                //判断请求发送是否成功
                if($pay_status =='PAY_FAILED'){
                    DB::rollBack();
                    OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                    Log::error("小程序扣款请求失败");
                }
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
                    'out_trade_no'  => $alipayUserId,        //业务系统授权码
                    'amount'        => $amount,              //交易金额；单位：分
                    'back_url'      => $backUrl,             //后台通知地址
                    'name'          => $subject,             //交易备注
                    'agreement_no'  => $agreementNo,         //支付平台代扣协议号
                    'user_id'       => $orderInfo['user_id'],//业务平台用户id
                ];

                try{
                    // 请求代扣接口
                    $withholding->deduct($withholding_data);

                }catch(\Exception $exc){
                    DB::rollBack();
                    \App\Lib\Common\LogApi::error('分期代扣错误', $withholding_data);
                    //修改扣款失败
                    OrderInstalment::save(['id'=>$instalmentId],['status'=>OrderInstalmentStatus::FAIL]);
                    //捕获异常 买家余额不足
                    if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                        OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                        Log::error("买家余额不足");
                        continue;
                    }
                }

                //发送短信
                $business_type = \App\Order\Modules\Repository\ShortMessage\Config::CHANNELID_OFFICAL;
                $orderNoticeObj  = new \App\Order\Modules\Service\OrderNotice($business_type, "", "InstalmentWithhold");
                $orderNoticeObj->notify($dataSms);

                //发送消息通知
                //通过用户id查询支付宝用户id
                $MessageSingleSendWord = new \App\Lib\AlipaySdk\sdk\MessageSingleSendWord($alipayUserId);
                //查询账单
                $year = substr($instalmentInfo['term'], 0, 4);
                $month = substr($instalmentInfo['term'], -2);
                $y = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), 0, 4);
                $m = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -5, -3);
                $d = substr(date('Y-m-d', strtotime($year . '-' . $month . '-01 +1 month -1 day')), -2);
                $messageArr = [
                    'amount' => $amount,
                    'bill_type' => '租金',
                    'bill_time' => $year . '年' . $month . '月1日' . '-' . $y . '年' . $m . '月' . $d . '日',
                    'pay_time' => date('Y-m-d H:i:s'),
                ];
                $b = $MessageSingleSendWord->PaySuccess($messageArr);
                if ($b === false) {
                    Log::error("发送消息通知错误-" . $MessageSingleSendWord->getError());
                }
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
        // 判断分期状态
        $instalmentId   = $params['instalment_id'];

        // 查询分期信息
        $instalmentInfo = OrderInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            // 提交事务
            return apiResponse([], $instalmentInfo, ApiStatus::$errCodes[$instalmentInfo]);
        }
        //分期状态
        if( $instalmentInfo['status'] != OrderInstalmentStatus::UNPAID && $instalmentInfo['status'] != OrderInstalmentStatus::FAIL){
            return apiResponse([], ApiStatus::CODE_71000, "该分期不允许提前还款");
        }

        //查询订单记录
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }
        // 订单状态
        if($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService && $orderInfo['freeze_type'] != \App\Order\Modules\Inc\OrderFreezeStatus::Non){
            return apiResponse([], ApiStatus::CODE_71000, "该订单不在服务中 不允许提前还款");
        }

        // 渠道
        $ChannelInfo = \App\Lib\Channel\Channel::getChannel($appid);
        if (!is_array($ChannelInfo)) {
            return apiResponse([], ApiStatus::CODE_10102, "channel_id 错误");
        }
        $channelId = intval($ChannelInfo['_channel']['id']);


        $youhui = 0;
        // 租金抵用券
        $couponInfo = \App\Lib\Coupon\Coupon::getUserCoupon($instalmentInfo['user_id']);
        if(is_array($couponInfo) && $couponInfo['youhui'] > 0){
            $youhui = $couponInfo['youhui'];
        }
        // 最小支付一分钱
        $amount = $instalmentInfo['amount'] - $youhui;
        $amount = $amount > 0 ? $amount : 0.01;

        //修改优惠券信息
        if($youhui > 0){
            // 创建优惠券使用记录
            $couponData = [
                'coupon_id'         => $couponInfo['coupon_id'],
                'discount_amount'   => $couponInfo['youhui'],
                'business_type'     => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                'business_no'       => $instalmentInfo['trade_no'],
            ];
            \App\Order\Modules\Repository\OrderCouponRepository::add($couponData);

            // 修改优惠券状态
            $couponStatus = \App\Lib\Coupon\Coupon::useCoupon([$couponInfo['coupon_id']]);
            if($couponStatus != ApiStatus::CODE_0){
                return apiResponse([],ApiStatus::CODE_50010);
            }
        }

        // 创建支付单
        $payData = [
            'userId'            => $instalmentInfo['user_id'],//用户ID
            'businessType'		=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,	// 业务类型
            'businessNo'		=> $instalmentInfo['trade_no'],	// 业务编号
            'paymentAmount'		=> $amount,	                    // Price 支付金额，单位：元
            'paymentFenqi'		=> '0',	// int 分期数，取值范围[0,3,6,12]，0：不分期
        ];
        $payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payData);

        //获取支付的url
        $url = $payResult->getCurrentUrl($channelId, [
            'name'=>'订单' .$orderInfo['order_no']. '分期'.$instalmentInfo['term'].'提前还款',
            'front_url' => $params['return_url'], //回调URL
        ]);

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
