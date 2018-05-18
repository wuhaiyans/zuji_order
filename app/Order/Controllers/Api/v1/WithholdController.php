<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\Payment\WithholdingApi;
use App\Lib\Payment\AlipayApi;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\OrderPayWithholdStatus;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderInstalment;
use App\Order\Modules\Service\OrderPayWithhold;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Order\Modules\Repository\ThirdInterface;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithholdController extends Controller
{

    /**
     *  签约代扣接口
     * [
     *      'user_id'           => '' // 用户ID
     *      'alipay_user_id'    => '' // 用户支付宝id（2088开头）
     *      'front_url'         => '' // 前端回跳地址
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'url' => '',//签约跳转url地址
     * ]
     */
    public function sign(Request $request){
        $params     = $request->all();

        $rules = [
            'user_id'           => 'required|int',
            'alipay_user_id'    => 'required',
            'front_url'         => 'required',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];


        $params['back_url'] = env("API_INNER_URL") . "/signNotify";

        $url = AlipayApi::withholdingUrl($params);

        if(!$url){
            return apiResponse(['url'=>''], ApiStatus::CODE_71008, "获取签约代扣URL地址失败");
        }

        return apiResponse(['url'=>$url['withholding_url']],ApiStatus::CODE_0,"success");


    }

    /**
     * 签约代扣回调接口
     * [
     *      'reason'            => '' // 错误原因
     *      'status'            => '' // 回调状态 0成功 1失败
     *      'out_agreement_no'  => '' // 支付平台签约协议号
     *      'agreement_no'      => '' // 订单系统签约协议号
     *      'user_id'           => '' // 用户id
     * ]
     */
    public function sign_notify(Request $request){
        $request    = $request->all();
        $params     = $request['params'];

        $rules = [
            'reason'            => 'required',
            'status'            => 'required|int',
            'out_agreement_no'  => 'required',
            'agreement_no'      => 'required',
            'user_id'           => 'required|int',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        //成功 则保存数据
        if($params['status'] == ApiStatus::CODE_0){

            $data  = [
                'withhold_no'       => $params['agreement_no'],
                'out_withhold_no'   => $params['out_agreement_no'],
                'user_id'           => $params['user_id'],
            ];
            $withhold = OrderPayWithhold::create_withhold($data);
            if(!$withhold){
                return apiResponse([],ApiStatus::CODE_71001, "异常错误");
            }

            return apiResponse([],ApiStatus::CODE_0, "操作成功");

        }

    }

    /*
     * 代扣协议查询
     * @$request array $request
     * [
     *		'user_id' => '',        //租机平台用户id
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

        $withholdInfo = OrderPayWithhold::find($userId);
        if(!is_array($withholdInfo)){
            return apiResponse([],ApiStatus::CODE_20001, "参数错误");
        }

        if(empty($withholdInfo) || $withholdInfo['withhold_status'] == OrderPayWithholdStatus::UNSIGN){
            return apiResponse(["status"=>"N"],ApiStatus::CODE_0);
        }

        return apiResponse(["status"=>"Y"],ApiStatus::CODE_0);

    }



    //解约代扣
    public function unsign(Request $request){
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        if(!$appid){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }
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
            return apiResponse( [], ApiStatus::CODE_71004, '获取用户支付宝id失败');
        }

        if( !$withholdInfo['withhold_no'] ){
            DB::rollBack();
            Log::error("获取用户代扣协议码失败");
            return apiResponse( [], ApiStatus::CODE_71004, '获取用户代扣协议码失败');
        }

        // 查看用户是否有未扣款的分期
        /* 如果有未扣款的分期信息，则不允许解约 */
        $n = OrderInstalment::query()->where([
            'agreement_no'=> $withholdInfo['withhold_no']])
            ->whereIn('status', [OrderInstalmentStatus::UNPAID,OrderInstalmentStatus::FAIL])->count();

        if( $n > 0 ){
            Log::error("[代扣解约]订单分期查询错误");
            return apiResponse( [], ApiStatus::CODE_50000, '解约失败，有未完成分期');
        }
        try {

            $data = [
                'user_id'           => $userId, //租机平台用户ID
                'alipay_user_id'    => $withholdInfo['out_withhold_no'], //用户支付宝id（2088开头）
                'agreement_no'      => $withholdInfo['withhold_no'], //签约协议号
                'back_url'          => env("API_INNER_URL") . "/unSignNotify", //回调地址
            ];

            $data['back_url'] =  env("API_INNER_URL") . "/unSignNotify";
            $b = WithholdingApi::unSign($appid, $data);
            if( !$b ){
                DB::rollBack();
                Log::error("[代扣解约]调用支付宝解约接口失败");
                return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
            }

            // 成功
            DB::commit();
            return apiResponse([],ApiStatus::CODE_0,"success");
        } catch (\Exception $exc) {
            return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');

        }
    }


    /**
     * 解约代扣回调接口
     */
    public function unsign_notify(Request $request){
        $request    = $request->all();
        $params     = $request['params'];

        $rules = [
            'reason'            => 'required',
            'status'            => 'required|int',
            'out_agreement_no'  => 'required',
            'agreement_no'      => 'required',
            'user_id'           => 'required|int',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];
        //成功 则保存数据
        if($params['status'] == ApiStatus::CODE_0){

            $userId     = $params['user_id'];
            $withhold   = OrderPayWithhold::unsign_withhold($userId);

            if($withhold !== true){
                return apiResponse([],ApiStatus::CODE_71001, "异常错误");
            }

            return apiResponse([],ApiStatus::CODE_0, "操作成功");
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
        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        if(!$appid){
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $params = filter_array($params, [
            'instalment_id'=>'required',
            'remark'=>'required',
        ]);

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

        $third = new ThirdInterface();
        $userInfo = $third->GetUser($orderInfo['user_id']);

        if( !is_array($userInfo )){
            DB::rollBack();
            return apiResponse([], $instalmentInfo, ApiStatus::$errCodes[$instalmentInfo]);
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderInstalmentRepository::save(['id'=>$instalmentId],$data);
        if(!$result){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71001, '扣款备注保存失败');
        }
        // 商品
        $subject = '订单-'.$instalmentInfo['order_no'].'-'.$instalmentInfo['goods_no'].'-第'.$instalmentInfo['times'].'期扣款';

        // 价格
        $amount = $instalmentInfo['amount']/100;
        if( $amount<0 ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71003, '扣款金额不能小于1分');
        }

        //扣款要发送的短信
        $data_sms =[
            'mobile'        => $userInfo['mobile'],
            'orderNo'       => $orderInfo['order_no'],
            'realName'      => $userInfo['realname'],
            'goodsName'     => $orderInfo['goods_name'],
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
            $withholding = new WithholdingApi();
            // 扣款
            $withholding_data = [
                'agreement_no'=>$agreementNo,
                'trade_no'=>$tradeNo,
                'subject'=>$subject,
                'amount'=>$amount,
            ];
            $withholding_b = $withholding->withhold($appid,$withholding_data);
            if (!$withholding_b) {
                DB::rollBack();
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
     *      'ids' => '', //分期表自增id数组
     * ]
     */
    public function multi_createpay(Request $request)
    {
        ini_set('max_execution_time', '0');

        $request    = $request->all();
        $appid      = $request['appid'];
        $params     = $request['params'];

        if (!$appid) {
            return apiResponse([], ApiStatus::CODE_20001, "参数错误");
        }

        $params = filter_array($params, [
            'ids' => 'required',
        ]);

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

            // 查询用户协议
            $third = new ThirdInterface();
            $userInfo = $third->GetUser($orderInfo['user_id']);

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
            $result = OrderInstalmentRepository::save(['id' => $instalmentId], $data);
            if (!$result) {
                DB::rollBack();
                Log::error("扣款备注保存失败");
                continue;
            }
            // 商品
            $subject = '订单-' . $instalmentInfo['order_no'] . '-' . $instalmentInfo['goods_no'] . '-第' . $instalmentInfo['times'] . '期扣款';

            // 价格
            $amount = $instalmentInfo['amount'] / 100;
            if ($amount < 0) {
                DB::rollBack();
                Log::error("扣款金额不能小于1分");
                continue;
            }

            //扣款要发送的短信
            $dataSms = [
                'mobile' => $userInfo['mobile'],
                'orderNo' => $orderInfo['order_no'],
                'realName' => $userInfo['realname'],
                'goodsName' => $orderInfo['goods_name'],
                'zuJin' => $amount,
            ];

            //判断支付方式
            //判断支付方式
            if ($orderInfo['pay_type'] == PayInc::MiniAlipay) {
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


            } else {

                // 支付宝用户的user_id
                $alipayUserId = $withholdInfo['out_withhold_no'];//$userInfo['alipay_user_id'];

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
                $withholding = new WithholdingApi();
                // 扣款
                $withholdingData = [
                    'agreement_no' => $agreementNo,
                    'trade_no' => $tradeNo,
                    'subject' => $subject,
                    'amount' => $amount,
                ];
                $withholdingResult = $withholding->withhold($appid, $withholdingData);
                if (!$withholdingResult) {
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


}
