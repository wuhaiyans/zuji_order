<?php

namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Lib\ApiStatus;
use Illuminate\Http\Request;
use App\Order\Modules\Service\OrderGoodsInstalment;
use App\Order\Modules\Repository\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Order\Modules\Repository\Pay\WithholdQuery;

class WithholdController extends Controller
{

    // 异步回调 状态
    protected $NotifyStatus = [
        'init'          => OrderInstalmentStatus::UNPAID,
        'success'       => OrderInstalmentStatus::SUCCESS,
        'failed'        => OrderInstalmentStatus::FAIL,
        'finished'      => OrderInstalmentStatus::CANCEL,
        'closed'        => OrderInstalmentStatus::CANCEL,
        'processing'    => OrderInstalmentStatus::PAYING,
    ];
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
        $params    = $request->all();
        // 参数过滤
        $rules = [
            'user_id'         => 'required|int',  //前端跳转地址
            'channel'         => 'required|int',  //签约渠道
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        $userId         = $params['params']['user_id'];
        $channel        = $params['params']['channel'];

        try{
            // 查询用户协议
            $withhold = WithholdQuery::getByUserChannel($userId,$channel);
            $payWithhold = $withhold->get_data();

            $data = [
                'agreement_no'		=> $payWithhold['out_withhold_no'], //【必选】string 支付系统签约编号
                'out_agreement_no'	=> $payWithhold['withhold_no'], //【必选】string 业务系统签约编号
                'user_id'			=> $userId, //【必选】string 业务系统用户ID
            ];

            $withholdInfo = \App\Lib\Payment\CommonWithholdingApi::queryAgreement($data);
            if(!$withholdInfo){
                return apiResponse([],ApiStatus::CODE_50000, "查询协议错误");
            }

            return apiResponse($withholdInfo,ApiStatus::CODE_0);

        }catch(\Exception $exc){
            return apiResponse([],ApiStatus::CODE_50000, "查询协议错误");
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

        // 参数过滤
        $rules = [
            'user_id'         => 'required|int',  //前端跳转地址
            'channel'         => 'required|int',  //签约渠道
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        $userId         = $params['params']['user_id'];
        $channel        = $params['params']['channel'];

        try{

            //开启事务
            DB::beginTransaction();

            // 查看用户是否有未扣款的分期
            /* 如果有未扣款的分期信息，则不允许解约 */
            $n = \App\Order\Models\OrderGoodsInstalment::query()->where([
                'user_id'=> $userId])
                ->whereIn('status', [OrderInstalmentStatus::UNPAID,OrderInstalmentStatus::FAIL]
                )->get()->count();

            if( $n > 0 ){
                DB::rollBack();
                Error::setError('[代扣解约]解约失败，有未完成分期');
                return apiResponse( [], ApiStatus::CODE_71010, '解约失败，有未完成分期');
            }

            // 查询用户协议
            $withhold = WithholdQuery::getByUserChannel($userId,$channel);
            $result  = $withhold->unsignApply();
            if(!$result){
                DB::rollBack();
                return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
            }

            return apiResponse( [], ApiStatus::CODE_0);

            // 提交事务
            DB::commit();
            return apiResponse([],ApiStatus::CODE_0,"success");
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
        $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            DB::rollBack();
            // 提交事务
            return apiResponse([], $instalmentInfo, ApiStatus::$errCodes[$instalmentInfo]);
        }

        // 订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }
        if($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71000, "[代扣]订单状态不在服务中");
        }

        //判断是否允许扣款
        $allow = OrderGoodsInstalment::allowWithhold($instalmentId);
        if(!$allow){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_71000, "不允许扣款" );
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderGoodsInstalment::save(['id'=>$instalmentId],$data);
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
            $miniParams['out_trans_no']     = $instalmentId;
            $miniParams['pay_amount']       = $amount;
            $miniParams['remark']           = $subject;
            $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );
            //判断请求发送是否成功
            if($pay_status == 'PAY_SUCCESS'){
                return apiResponse([], ApiStatus::CODE_0, '小程序扣款操作成功');
            }elseif($pay_status =='PAY_FAILED'){
                OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term']);
                return apiResponse([], ApiStatus::CODE_35006, '小程序扣款请求失败');
            }elseif($pay_status == 'PAY_INPROGRESS'){
                return apiResponse([], ApiStatus::CODE_35007, '小程序扣款处理中请等待');
            }else{
                return apiResponse([], ApiStatus::CODE_50000, '小程序扣款处理失败（内部失败）');
            }
        }else {
            // 代扣协议编号
            $channel = \App\Order\Modules\Repository\Pay\Channel::Alipay;   //暂时保留
            // 查询用户协议
            $withhold = WithholdQuery::getByUserChannel($instalmentInfo['user_id'], $channel);

            $withholdInfo = $withhold->getData();

            $agreementNo = $withholdInfo['out_withhold_no'];
            if (!$agreementNo) {
                DB::rollBack();
                return apiResponse([], ApiStatus::CODE_71004, '用户代扣协议编号错误');
            }
            // 代扣接口
            $withholding = new \App\Lib\Payment\CommonWithholdingApi;

            $backUrl = env("API_INNER_URL") . "/createpayNotify";

            $withholding_data = [
                'out_trade_no'  => $instalmentInfo['id'], //业务系统业务吗
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
                \App\Lib\Common\LogApi::error('分期代扣错误', [$exc->getMessage()]);
                //捕获异常 买家余额不足
                if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term']);
                    return apiResponse([], ApiStatus::CODE_71004, '买家余额不足');
                } else {
                    return apiResponse([], ApiStatus::CODE_71006, '扣款失败');
                }
            }

            //发送短信通知 支付宝内部通知
            $notice = new \App\Order\Modules\Service\OrderNotice(
                OrderStatus::BUSINESS_FENQI,
                $instalmentInfo['trade_no'],
                "InstalmentWithhold");
            $notice->notify();

            // 发送支付宝消息通知
            $notice->alipay_notify();

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

            // 分期详情
            $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
            if (!is_array($instalmentInfo)) {
                Log::error("分期信息查询失败");
                continue;
            }

            // 判断是否允许扣款
            $allow = OrderGoodsInstalment::allowWithhold($instalmentId);
            if (!$allow) {
                Log::error("不允许扣款");
                continue;
            }

            //开启事务
            DB::beginTransaction();

            // 状态在支付中或已支付时，直接返回成功
            if ($instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS && $instalmentInfo['status'] = OrderInstalmentStatus::PAYING) {
                continue;
            }

            // 订单
            $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
            if (!$orderInfo) {
                DB::rollBack();
                Log::error("数据异常");
                continue;
            }

            // 保存 备注，更新状态
            $data = [
                'remark' => $remark,
                'status' => OrderInstalmentStatus::PAYING,// 扣款中
            ];
            $result = OrderGoodsInstalment::save(['id' => $instalmentId], $data);
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
                $miniParams['out_trans_no']     = $instalmentId;
                $miniParams['pay_amount']       = $amount;
                $miniParams['remark']           = $subject;
                $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );

                //判断请求发送是否成功
                if($pay_status =='PAY_FAILED'){
                    DB::rollBack();
                    OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term']);
                    Log::error("小程序扣款请求失败");
                }
            } else {
                // 代扣协议编号
                $channel = \App\Order\Modules\Repository\Pay\Channel::Alipay;   //暂时保留
                // 查询用户协议
                $withhold = WithholdQuery::getByUserChannel($instalmentInfo['user_id'], $channel);

                $withholdInfo = $withhold->getData();

                // 代扣协议编号
                $agreementNo = $withholdInfo['out_withhold_no'];
                if (!$agreementNo) {
                    DB::rollBack();
                    Log::error("用户代扣协议编号错误");
                    continue;
                }
                // 代扣接口
                $withholding = new \App\Lib\Payment\CommonWithholdingApi;
                $backUrl = env("API_INNER_URL") . "/createpayNotify";

                $withholding_data = [
                    'out_trade_no'  => $instalmentInfo['id'],   //业务系统业务吗
                    'amount'        => $amount,                 //交易金额；单位：分
                    'back_url'      => $backUrl,                //后台通知地址
                    'name'          => $subject,                //交易备注
                    'agreement_no'  => $agreementNo,            //支付平台代扣协议号
                    'user_id'       => $orderInfo['user_id'],   //业务平台用户id
                ];

                try{
                    // 请求代扣接口
                    $withholding->deduct($withholding_data);

                }catch(\Exception $exc){
                    DB::rollBack();
                    \App\Lib\Common\LogApi::error('分期代扣错误', $withholding_data);
                    //修改扣款失败
                    OrderGoodsInstalment::save(['id'=>$instalmentId],['status'=>OrderInstalmentStatus::FAIL]);
                    //捕获异常 买家余额不足
                    if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                        OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term']);
                        Log::error("买家余额不足");
                        continue;
                    }
                }

                //发送短信通知 支付宝内部通知
                $notice = new \App\Order\Modules\Service\OrderNotice(
                    OrderStatus::BUSINESS_FENQI,
                    $instalmentInfo['trade_no'],
                    "InstalmentWithhold");
                $notice->notify();

                // 发送支付宝消息通知
                $notice->alipay_notify();

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
        $rules = [
            'return_url'        => 'required',
            'instalment_id'     => 'required|int',
            'channel'           => 'required|int',
        ];

        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([], $validateParams['code']);
        }
        $params = $params['params'];
        // 判断分期状态
        $instalmentId   = $params['instalment_id'];
        // 渠道
        $channelId      = $params['channel'];



        // 查询分期信息
        $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            // 提交事务
            return apiResponse([], $instalmentInfo, ApiStatus::$errCodes[$instalmentInfo]);
        }

        //分期状态
        if( $instalmentInfo['status'] != OrderInstalmentStatus::UNPAID && $instalmentInfo['status'] != OrderInstalmentStatus::FAIL){
            return apiResponse([], ApiStatus::CODE_71000, "该分期不允许提前还款");
        }

        //查询订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }

        // 订单状态
        if($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService && $orderInfo['freeze_type'] != \App\Order\Modules\Inc\OrderFreezeStatus::Non){
            return apiResponse([], ApiStatus::CODE_71000, "该订单不在服务中 不允许提前还款");
        }

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
                'business_no'       => $instalmentInfo['id'],
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
            'businessNo'		=> $instalmentId,	// 业务编号
            'paymentAmount'		=> $amount,	                    // Price 支付金额，单位：元
            'paymentFenqi'		=> '0',	// int 分期数，取值范围[0,3,6,12]，0：不分期
        ];
        $payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payData);

        //获取支付的url
        $url = $payResult->getCurrentUrl($channelId, [
            'name'=>'订单' .$orderInfo['order_no']. '分期'.$instalmentInfo['term'].'提前还款',
            'front_url' => $params['return_url'], //回调URL
        ]);

        return apiResponse($url,ApiStatus::CODE_0);


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

            // 查询分期信息
            $instalmentInfo = OrderGoodsInstalment::queryInfo(['id'=>$params['out_no']]);
            if( !is_array($instalmentInfo)){
                // 提交事务
                echo "FAIL";exit;
            }

            // 分期数据
            if(!isset($this->status[$params['status']])){
                echo "FAIL";exit;
            }

            $data = [
                'status'        => $this->status[$params['status']],
                'update_time'   => time(),
                'remark'        => '提前还款',
            ];

            $b = OrderGoodsInstalment::save(['trade_no'=>$params['out_trade_no']], $data);
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

            try{
                // 查询用户协议
                $withhold = WithholdQuery::getByWithholdNo( $params['out_agreement_no'] );
                $withhold->unsignSuccess();
            } catch(\Exception $exc){

                echo "FAIL";exit;
            }
        }

        echo "SUCCESS";
    }

    /**
     * 提前还款异步回调接口
     * @requwet Array
     * [
     *       "payment_no":"mock",            //类型：String  必有字段  备注：支付平台支付码
     *       "out_no":"mock",                //类型：String  必有字段  备注：订单平台支付码
     *       "status":"mock",                //类型：String  必有字段  备注：init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
     *       "reason":"mock"                 //类型：String  必有字段  备注：失败理由，成功无此字段
     * ]
     * @return String FAIL：失败  SUCCESS：成功
     */
    public function repaymentNotify(Request $request){
        $params     = $request->all();

        $rules = [
            'payment_no'    => 'required',
            'out_no'        => 'required',
            'status'        => 'required',
            'reason'        => 'required',
        ];
        // 参数过滤
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }

        // 解约成功 修改协议表
        $params = $params['params'];

        // 查询分期信息
        $instalmentInfo = OrderInstalment::queryInfo(['trade_no'=>$params['out_no']]);
        if( !is_array($instalmentInfo)){
            // 提交事务
            echo "FAIL";exit;
        }

        if(!isset($this->status[$params['status']])){
            echo "FAIL";exit;
        }

        $data = [
            'status'        => $this->status[$params['status']],
            'trade_no'      => $params['out_no'],
            'out_trade_no'  => $params['payment_no'],
            'update_time'   => time(),
            'remark'        => '提前还款',
        ];

        // 修改分期状态
        $result = \App\Order\Modules\Service\OrderInstalment::save(['trade_no'=>$params['out_no']],$data);
        if(!$result){
            echo "FAIL";exit;
        }

        // 还原租金优惠券
        if($params['status'] == "failed"){
            $counponWhere = [
                'business_type' => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                'business_no'   => $params['out_no'],
            ];
            $counponInfo  = \App\Order\Modules\Repository\OrderCouponRepository::find($counponWhere);
            if(!empty($counponInfo)){
                // 修改优惠券状态
                $couponStatus = \App\Lib\Coupon\Coupon::setCoupon(['user_id'=>$instalmentInfo['user_id'],'coupon_id'=>$counponInfo['coupon_id']]);
                if($couponStatus != ApiStatus::CODE_0){
                    echo "FAIL";exit;
                }
            }
        }

        echo "SUCCESS";
    }



}
