<?php

namespace App\Order\Controllers\Api\v1;

use App\Lib\Common\LogApi;
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

    /**
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
        $uid        = $params['userinfo']['uid'];
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

        // 用户验证
        if($uid != $userId){
            return apiResponse([], ApiStatus::CODE_50000, "用户信息错误");
        }

        try{
            // 查询用户协议
            $withhold = WithholdQuery::getByUserChannel($userId,$channel);
            $payWithhold  = $withhold->getData();
            $allowUnsign  = $withhold->getCounter() == 0 ? "Y" : "N";

            // 支付系统查询代扣签约状态
            $withholdInfo = \App\Lib\Payment\CommonWithholdingApi::queryAgreement([
                'agreement_no'		=> $payWithhold['out_withhold_no'], //【必选】string 支付系统签约编号
                'out_agreement_no'	=> $payWithhold['withhold_no'], //【必选】string 业务系统签约编号
                'user_id'			=> $userId, //【必选】string 业务系统用户ID
            ]);

            $withholdStatus = [
                "withholding" => $withholdInfo['status'] == "signed" ? 'Y':'N',
                "allowUnsign" => $allowUnsign
            ];
            return apiResponse($withholdStatus, ApiStatus::CODE_0);
        }catch(\Exception $exc){

            $withholdStatus = [
                "withholding" => "N",
                "allowUnsign" => "N"
            ];

            return apiResponse($withholdStatus,ApiStatus::CODE_0);
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
        $uid        = $params['userinfo']['uid'];
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
        // 用户验证
        if($uid != $userId){
            return apiResponse([], ApiStatus::CODE_50000, "用户信息错误");
        }
        try{

            //开启事务
            DB::beginTransaction();

            // 判断用户代扣协议是否允许 解约
            $withhold = WithholdQuery::getByUserChannel($userId,$channel);
            if($withhold->getCounter() != 0){
                DB::rollBack();
                return apiResponse( [], ApiStatus::CODE_50000, '不允许解约');
            }

            $result   = $withhold->unsignApply();
            if(!$result){
                DB::rollBack();
                return apiResponse( [], ApiStatus::CODE_50000, '服务器繁忙，请稍候重试...');
            }
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
        ini_set('max_execution_time', '0');
        LogApi::setSource('withhold_createpay');
        $params     = $request->all();
        $operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
        if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
            return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
        }

        $rules = [
            'instalment_id'     => 'required|int',
        ];
        $validateParams = $this->validateParams($rules,$params);
        if ($validateParams['code'] != 0) {
            return apiResponse([],$validateParams['code']);
        }
        $params = $params['params'];

        $instalmentId   = $params['instalment_id'];
        $remark         = !empty($params['remark']) ? $params['remark'] : "主动扣款";

        // 查询分期信息
        $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            // 提交事务
            return apiResponse([], $instalmentInfo, ApiStatus::$errCodes[$instalmentInfo]);
        }

        $instalmentKey = "instalmentWithhold_" . $instalmentId;
        // 频次限制
        if(redisIncr($instalmentKey, 300) > 1){
            return apiResponse([],ApiStatus::CODE_92500,'当前分期正在操作，不能重复操作');
        }

        // 生成交易码
        $business_no = createNo();
        // 扣款交易码
        if( $instalmentInfo['business_no'] == '' ){
            // 1)记录租机交易码
            $b = OrderGoodsInstalment::save(['id'=>$instalmentId],['business_no'=>$business_no]);
            if( $b === false ){
                return apiResponse([], ApiStatus::CODE_32002, "数据异常");
            }
            $instalmentInfo['business_no'] = $business_no;
        }
        $business_no = $instalmentInfo['business_no'];

        // 订单
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            return apiResponse([], ApiStatus::CODE_32002, "数据异常");
        }
        if($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService){
            return apiResponse([], ApiStatus::CODE_71000, "[代扣]订单状态不在服务中");
        }

        //判断是否允许扣款
        $allow = OrderGoodsInstalment::allowWithhold($instalmentId);
        if(!$allow){
            return apiResponse([], ApiStatus::CODE_71000, "不允许扣款" );
        }

        // 商品
        $subject = $instalmentInfo['order_no'].'-'.$instalmentInfo['times'].'-期扣款';

        // 价格
        // 2018-08-09 注意：浮点数的乘法计算时，会得到一个另类的值（xxx.999999）,在特殊场景中打印时会出现
        // 例如json_encode()时，打印成 xxx.9999999
        // 解决办法： 将结果值 1）先转成字符串类型的值，2）再转换成想用的类型（想使用int值，则再转成init）
        $amount = intval( strval($instalmentInfo['amount'] * 100) );
        if( $amount<0 ){
            return apiResponse([], ApiStatus::CODE_71003, '扣款金额不能小于1分');
        }

        // 修改分期支付中状态
        $paying = \App\Order\Models\OrderGoodsInstalment::query()
            ->where(['id' => $instalmentInfo['id']])
            ->update(['status'=>OrderInstalmentStatus::PAYING,'update_time' => time()]);
        if(!$paying){
            LogApi::error('[crontabCreatepay]修改分期支付中状态：'.$subject);
            return apiResponse([], ApiStatus::CODE_71006, '扣款失败');
        }

        //判断支付方式
        if( $orderInfo['pay_type'] == PayInc::MiniAlipay ){
            //获取订单的芝麻订单编号
            $miniOrderInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo( $instalmentInfo['order_no'] );
            if( empty($miniOrderInfo) ){
                LogApi::info('本地小程序确认订单回调记录查询失败',$orderInfo['order_no']);
                return apiResponse([],ApiStatus::CODE_35003,'本地小程序确认订单回调记录查询失败');
            }
            //芝麻小程序扣款请求
            $miniParams['out_order_no']     = $miniOrderInfo['order_no'];
            $miniParams['zm_order_no']      = $miniOrderInfo['zm_order_no'];
            $miniParams['app_id'] = $miniOrderInfo['app_id'];
            //扣款交易号
            $miniParams['out_trans_no']     = $business_no;
            $miniParams['pay_amount']       = $instalmentInfo['amount'];
            $miniParams['remark']           = $subject;
            $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );
            //判断请求发送是否成功
            if($pay_status == 'PAY_SUCCESS'){
                // 提交事务
                return apiResponse([], ApiStatus::CODE_0, '小程序扣款操作成功');
            }elseif($pay_status =='PAY_FAILED'){
                OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId);
                // 提交事务
                return apiResponse([], ApiStatus::CODE_35006, '小程序扣款请求失败');
            }elseif($pay_status == 'PAY_INPROGRESS'){
                // 提交事务
                return apiResponse([], ApiStatus::CODE_35007, '小程序扣款处理中请等待');
            }else{
                // 事物回滚
                return apiResponse([], ApiStatus::CODE_50000, '小程序扣款处理失败（内部失败或芝麻处理错误）');
            }
        }else {

            // 代扣协议编号
            $channel = \App\Order\Modules\Repository\Pay\Channel::Alipay;   //暂时保留
            // 查询用户协议
            $withhold = WithholdQuery::getByUserChannel($instalmentInfo['user_id'], $channel);

            $withholdInfo = $withhold->getData();

            $agreementNo = $withholdInfo['out_withhold_no'];
            if (!$agreementNo) {
                return apiResponse([], ApiStatus::CODE_71004, '用户代扣协议编号错误');
            }
            // 代扣接口
            $withholding = new \App\Lib\Payment\CommonWithholdingApi;

            $backUrl = config('app.url') . "/order/pay/withholdCreatePayNotify";

            $withholding_data = [
                'agreement_no'  => $agreementNo,            //支付平台代扣协议号
                'out_trade_no'  => $business_no,            //业务系统业务码
                'amount'        => $amount,                 //交易金额；单位：分
                'back_url'      => $backUrl,                //后台通知地址
                'name'          => $subject,                //交易备注
                'user_id'       => $orderInfo['user_id'],   //业务平台用户id
            ];

            try{
                // 请求代扣接口
                $withholdStatus = $withholding->deduct($withholding_data);

                LogApi::error('[createpay_withhold]分期代扣请求返回结果-' , $withholdStatus);


                if( !isset($withholdStatus['status']) || $withholdStatus['status'] != 'processing'){

                    LogApi::error('[createpay]分期代扣错误,返回的结果及参数分别为：', [$withholdStatus,$withholding_data]);
                    OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId);
                }
                LogApi::error('[createpay_withhold]分期代扣请求-' . $instalmentInfo['order_no'] , $withholdStatus);

            }catch(\App\Lib\ApiException $exc){
                LogApi::error('[createpay]分期代扣失败', $exc);
                return apiResponse([], ApiStatus::CODE_71006, $exc->getMessage());

            }catch(\Exception $exc){
                LogApi::error('分期代扣错误', [$exc->getMessage()]);
                OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId);
                //捕获异常 买家余额不足
                if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    return apiResponse([], ApiStatus::CODE_71004, '买家余额不足');
                } else {
                    return apiResponse([], ApiStatus::CODE_71006, '扣款失败');
                }
            }

        }
        //记录日志
        $logData = [
            'order_no'      => $instalmentInfo['order_no'],
            'action'        => '分期扣款',
            'business_key'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,//此处用常量
            'business_no'   => $business_no,
            'goods_no'      => $instalmentInfo['goods_no'],
            'operator_id'   => $operateUserInfo['uid'],
            'operator_name' => $operateUserInfo['username'],
            'operator_type' => $operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
            'msg'           => '分期扣款',
        ];
        $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add($logData);
        if( !$goodsLog ){
            LogApi::error("[createpay]分期扣款日志失败",$logData);
            return apiResponse([], ApiStatus::CODE_71006, '分期扣款日志失败');
        }

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
    public function multiCreatepay(Request $request)
    {
        ini_set('max_execution_time', '0');
        LogApi::setSource('withhold_multi_createpay');

        $params     = $request->all();
        $operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
        if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
            return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
        }
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
                LogApi::error("multiCreatepay参数错误");
                continue;
            }

            $instalmentKey = "instalmentWithhold_" . $instalmentId;
            // 频次限制
            if(redisIncr($instalmentKey, 300) > 1){
                LogApi::error("multiCreatepay当前分期正在操作，不能重复操作");
                continue;
            }

            $remark = "代扣多项扣款";

            // 分期详情
            $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
            if (!is_array($instalmentInfo)) {
                LogApi::error("multiCreatepay分期信息查询失败");
                continue;
            }

            // 生成交易码
            $business_no = createNo();
            // 扣款交易码
            if( $instalmentInfo['business_no'] == '' ){
                // 1)记录租机交易码
                $b = OrderGoodsInstalment::save(['id'=>$instalmentId],['business_no'=>$business_no]);
                if( $b === false ){
                    LogApi::error("multiCreatepay数据异常");
                    continue;
                }
                $instalmentInfo['business_no'] = $business_no;
            }
            $business_no = $instalmentInfo['business_no'];

            // 判断是否允许扣款
            $allow = OrderGoodsInstalment::allowWithhold($instalmentId);
            if (!$allow) {
                LogApi::error("multiCreatepay不允许扣款");
                continue;
            }


            // 状态在支付中或已支付时，直接返回成功
            if ($instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS && $instalmentInfo['status'] = OrderInstalmentStatus::PAYING) {
                continue;
            }

            // 订单
            $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
            if (!$orderInfo) {
                LogApi::error("multiCreatepay数据异常");
                continue;
            }


            // 商品
            $subject = $instalmentInfo['order_no'].'-'.$instalmentInfo['times'].'-期扣款';

            // 价格
            // $amount = $instalmentInfo['amount'] * 100;// 存在浮点计算精度问题
            $amount = intval( strval($instalmentInfo['amount'] * 100) );
            if ($amount < 0) {
                LogApi::error("multiCreatepay扣款金额不能小于1分");
                continue;
            }

            // 修改分期支付中状态
            $paying = \App\Order\Models\OrderGoodsInstalment::query()
                ->where(['id' => $instalmentId])
                ->update(['status'=>OrderInstalmentStatus::PAYING,'update_time' => time()]);
            if(!$paying){
                LogApi::error('[multiCreatepay]修改分期支付中状态：'.$subject);
            }

            //判断支付方式 小程序
            if ($orderInfo['pay_type'] == PayInc::MiniAlipay) {
                //获取订单的芝麻订单编号
                $miniOrderInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo( $instalmentInfo['order_no'] );
                if( empty($miniOrderInfo) ){
                    LogApi::info('本地小程序确认订单回调记录查询失败',$orderInfo['order_no']);
                    continue;
                }
                //芝麻小程序扣款请求
                $miniParams['out_order_no']     = $miniOrderInfo['order_no'];
                $miniParams['zm_order_no']      = $miniOrderInfo['zm_order_no'];
                $miniParams['app_id'] = $miniOrderInfo['app_id'];
                //扣款交易号
                $miniParams['out_trans_no']     = $business_no;
                $miniParams['pay_amount']       = $instalmentInfo['amount'];
                $miniParams['remark']           = $subject;
                $pay_status = \App\Lib\Payment\mini\MiniApi::withhold( $miniParams );
                //判断请求发送是否成功
                if($pay_status == 'PAY_SUCCESS'){
                    continue;
                }elseif($pay_status =='PAY_FAILED'){
                    OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId);
                    continue;
                }elseif($pay_status == 'PAY_INPROGRESS'){
                    continue;
                }else{
                    continue;
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

                    Log::error("multiCreatepay用户代扣协议编号错误");
                    continue;
                }
                // 代扣接口
                $withholding = new \App\Lib\Payment\CommonWithholdingApi;
                $backUrl = config('app.url') . "/order/pay/withholdCreatePayNotify";

                $withholding_data = [
                    'agreement_no'  => $agreementNo,            //支付平台代扣协议号
                    'out_trade_no'  => $business_no,               //业务系统业务吗
                    'amount'        => $amount,                 //交易金额；单位：分
                    'back_url'      => $backUrl,                //后台通知地址
                    'name'          => $subject,                //交易备注
                    'user_id'       => $orderInfo['user_id'],   //业务平台用户id
                ];

                try{
                    // 请求代扣接口
                    $withStatus = $withholding->deduct($withholding_data);

                    if( !isset($withStatus['status']) || $withStatus['status'] != 'processing'){

                        LogApi::error('[multiCreatepay]分期代扣错误,返回的结果及参数分别为：', [$withStatus,$withholding_data]);
                        OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId);
                    }

                    \App\Lib\Common\LogApi::error('multiCreatepay分期代扣返回:'.$instalmentInfo['order_no'], $withStatus);
                }catch(\Exception $exc){

                    \App\Lib\Common\LogApi::error('multiCreatepay分期代扣错误', $withholding_data);
                    OrderGoodsInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId);
                    //捕获异常 买家余额不足
                    if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {

                        LogApi::error("multiCreatepay买家余额不足");
                        continue;
                    }
                }

            }

            //记录日志
            $logData = [
                'order_no'      => $instalmentInfo['order_no'],
                'action'        => '分期扣款',
                'business_key'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,//此处用常量
                'business_no'   => $business_no,
                'goods_no'      => $instalmentInfo['goods_no'],
                'operator_id'   => $operateUserInfo['uid'],
                'operator_name' => $operateUserInfo['username'],
                'operator_type' => $operateUserInfo['type']==1?\App\Lib\PublicInc::Type_Admin:\App\Lib\PublicInc::Type_User,//此处用常量
                'msg'           => '多项扣款',
            ];
            $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add($logData);
            if( !$goodsLog ){
                LogApi::error("multiCreatepay多项扣款失败",$logData);
                continue;
            }

        }
        return apiResponse([], ApiStatus::CODE_0, "success");

    }

    /**
     *
     * Author: heaven
     *  计算定时扣款的总数
     */
    public function crontabCreatepayNum()
    {
        // 查询当天没有扣款记录数据
        $dateTime  = strtotime(date('Y-m-d',time()));
        $whereArray =
            [
                ['withhold_day', '<=', $dateTime],
                ['withhold_day', '>', 0],
            ];
        $needPayArray = [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL,OrderInstalmentStatus::PAYING];
        $total = DB::table('order_goods_instalment')
            ->where($whereArray)
            ->whereIn('status', $needPayArray)
            ->count();

        //根据进程数量，判断每页显示的条数
        $maxProces = 20;
        if (ceil($total / 100)>$maxProces ) {

            $limit  = intval(ceil($total/20));
            $processNum = $maxProces;

        } else {

            $limit  = 100;
            $processNum = intval(ceil($total/100));
        }
        $Array = [];
        for($i = 1; $i <= $processNum; $i++ ){
            $offSet = ($i-1)*$limit;
            $result = DB::table('order_goods_instalment')
                ->select('id')
                ->where($whereArray)
                ->whereIn('status', $needPayArray)
                ->orderBy('id',"ASC")
                ->offset($offSet)
                ->take($limit)
                ->get();

            $payNum = objectToArray($result);
            $minValue  = $payNum[0]['id'];
            $maxValue = $payNum[count($payNum)-1]['id'];

            $Array[] = $minValue.'-'.$maxValue;
        }
        $json = json_encode($Array);
        return $json;


    }

    /**
     * 定时任务扣款
     */
    public static function crontabCreatepay($minId,$maxId)
    {
        LogApi::setSource('crontab_withhold_createpay');
        LogApi::info('[crontabCreatepay]进入定时扣款minId：'.$minId . '----maxId:'. $maxId);

        // 执行时间
        ini_set('max_execution_time', '0');

        //需要扣款的状态
        $needPayArray = [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::FAIL,OrderInstalmentStatus::PAYING];

        // 查询当天没有扣款记录数据
        $dateTime   = strtotime(date('Y-m-d',time()));

        $whereArray =
            [
                ['id', '>=', $minId],
                ['id', '<=', $maxId],
                ['withhold_day', '>', 0],
                ['withhold_day', '<=', $dateTime],
            ];
        $total = \App\Order\Models\OrderGoodsInstalment::query()
            ->where($whereArray)
            ->whereIn('status', $needPayArray)
            ->count();
        LogApi::info('[crontabCreatepay]需要扣款的数量'.$total);
        if($total == 0){
            return;
        }

        $limit          = 100;
        $page           = 1;
        $startOffset    = ($page - 1) * $limit;
        /*
         * 隔10秒执行一次扣款
         */
        $time           = 10;
        $totalpage      = ceil($total/$limit);
        LogApi::info('[crontabCreatepay]需要扣款的总页数'.$totalpage);

        while(true) {

            if ($page > $totalpage ){
                //记录执行成功的总数和失败的总数
                $whereFailArray =
                    [
                        ['id', '>=', $minId],
                        ['id', '<=', $maxId],
                        ['withhold_day', '>', 0],
                        ['withhold_day', '<=', $dateTime],
                        ['status', '=', OrderInstalmentStatus::FAIL]
                    ];
                $failTotal = \App\Order\Models\OrderGoodsInstalment::query()
                    ->where($whereFailArray)
                    ->count();
                LogApi::info('[crontabCreatepay]脚本执行完成后待扣款或者扣款失败的总条数：'.$failTotal);

                $whereSuccessArray =
                    [
                        ['id', '>=', $minId],
                        ['id', '<=', $maxId],
                        ['withhold_day', '>', 0],
                        ['withhold_day', '<=', $dateTime],
                        ['status', '=', OrderInstalmentStatus::SUCCESS]
                    ];
                $successTotal = \App\Order\Models\OrderGoodsInstalment::query()
                    ->where($whereSuccessArray)
                    ->count();
                LogApi::info('[crontabCreatepay]脚本执行完成后扣款成功的总条数：'.$successTotal);
                exit;
            }

            // 查询数据
            $result =  \App\Order\Models\OrderGoodsInstalment::query()
                ->where($whereArray)
                ->whereIn('status', $needPayArray)
                ->orderBy('id','ASC')
                ->offset($startOffset)
                ->limit($limit)
                ->get()
                ->toArray();
            if(empty($result)){
                return;
            }

            foreach($result as $item) {
                // 商品
                $subject = $item['order_no'].'-'.$item['term'].'-'.$item['times'];

                LogApi::info('[crontabCreatepay]操作的扣款订单和期数：'.$subject.'-扣款');

                $instalmentKey = "instalmentWithhold_" . $item['id'];
                // 频次限制
                if(redisIncr($instalmentKey, 300) > 1){
                    LogApi::error('[crontabCreatepay]当前分期正在操作，不能重复操作,操作的key:'.$instalmentKey);
                    continue;
                }

                // 扣款交易码
                if( $item['business_no'] == '' ){
                    // 生成交易码
                    $business_no = createNo();
                    // 1)记录租机交易码
                    $b = OrderGoodsInstalment::save(['id'=>$item['id']],['business_no'=>$business_no]);
                    if( $b === false ){
                        LogApi::error('[crontabCreatepay]分期扣款交易码保存失败',[
                            'id' => $item['id'],
                            'business_no'=>$business_no,
                        ]);
                        continue;
                    }
                    $item['business_no'] = $business_no;
                }
                $business_no = $item['business_no'];

                // 订单
                $orderInfo = OrderRepository::getInfoById($item['order_no']);
                if (!$orderInfo) {
                    LogApi::error('[crontabCreatepay]订单不存在：'.$subject);
                    continue;
                }

                if ($orderInfo['order_status'] != \App\Order\Modules\Inc\OrderStatus::OrderInService) {
                    LogApi::error('[crontabCreatepay]订单状态不处于租用中：'.$subject);
                    continue;
                }


                if(!(in_array($item['status'],$needPayArray))){
                    LogApi::error('[crontabCreatepay]分期状态不可扣款：'.$subject);
                    continue;
                }

                if ($item['amount'] <= 0) {
                    LogApi::error('[crontabCreatepay]扣款金额错误<=0：'.$subject);
                    continue;
                }
                // 价格单位转换
                $amount = intval( strval($item['amount'] * 100) );


                // 分期若在 支付中状态 则跳出
                if( $item['status'] == OrderInstalmentStatus::PAYING ){
                    /**
                     * 分期时间update_time超过一个小时 则修改为失败状态
                     */
                    $pastTimes = time() - 3600;
                    if($pastTimes >= $item['update_time']){
                        $updateFailStatus = \App\Order\Models\OrderGoodsInstalment::query()
                            ->where(['id' => $item['id']])
                            ->update(['status' => OrderInstalmentStatus::FAIL,'update_time' => time()]);
                        if(!$updateFailStatus){
                            LogApi::error('[crontabCreatepay]修改分期状态支付中为失败：'.$subject);
                        }
                    }
                    continue;

                }else{
                    // 修改分期支付中状态
                    $paying = \App\Order\Models\OrderGoodsInstalment::query()
                        ->where(['id' => $item['id']])
                        ->update(['status' => OrderInstalmentStatus::PAYING,'update_time' => time()]);
                    if(!$paying){
                        LogApi::error('[crontabCreatepay]修改分期支付中状态：'.$subject);
                    }
                }


                //判断支付方式
                if ($orderInfo['pay_type'] == PayInc::MiniAlipay) {
                    //获取订单的芝麻订单编号
                    $miniOrderInfo = \App\Order\Modules\Repository\OrderMiniRepository::getMiniOrderInfo($item['order_no']);
                    if (empty($miniOrderInfo)) {
                        LogApi::error('[crontabCreatepay]本地小程序确认订单回调记录查询失败：'.$subject);
                        continue;
                    }
                    //芝麻小程序扣款请求
                    $miniParams['out_order_no'] = $miniOrderInfo['order_no'];
                    $miniParams['zm_order_no'] = $miniOrderInfo['zm_order_no'];
                    $miniParams['app_id'] = $miniOrderInfo['app_id'];
                    //扣款交易号
                    $miniParams['out_trans_no'] = $item['business_no'];
                    $miniParams['pay_amount'] = $item['amount'];
                    $miniParams['remark'] = $subject;
                    $pay_status = \App\Lib\Payment\mini\MiniApi::withhold($miniParams);
                    LogApi::info('[crontabCreatepay]小程序发起扣款后：'.$subject.':扣款的结果：'.$pay_status.':发起的参数.',$miniParams);
                    //判断请求发送是否成功
                    if($pay_status == 'PAY_SUCCESS'){
                        continue;
                    }elseif($pay_status =='PAY_FAILED'){
                        OrderGoodsInstalment::instalment_failed($item['fail_num'], $item['id']);
                        continue;
                    }elseif($pay_status == 'PAY_INPROGRESS'){
                        continue;
                    }else{
                        continue;
                    }
                } else {

                    try{
                        // 代扣协议编号
                        $channel = \App\Order\Modules\Repository\Pay\Channel::Alipay;   //暂时保留
                        // 查询用户协议
                        $withhold = WithholdQuery::getByUserChannel($item['user_id'], $channel);

                        $withholdInfo = $withhold->getData();

                        $agreementNo = $withholdInfo['out_withhold_no'];
                        if (!$agreementNo) {
                            LogApi::error('[crontabCreatepay]用户代扣协议编号错误：'.$subject);
                            continue;
                        }
                        // 代扣接口
                        $withholding = new \App\Lib\Payment\CommonWithholdingApi;
                        $backUrl = config('app.url') . "/order/pay/withholdCreatePayNotify";

                        $withholding_data = [
                            'agreement_no'  => $agreementNo,            //支付平台代扣协议号
                            'out_trade_no'  => $business_no,             //业务系统业务吗
                            'amount'        => $amount,                 //交易金额；单位：分
                            'back_url'      => $backUrl,                //后台通知地址
                            'name'          => $subject,                //交易备注
                            'user_id'       => $orderInfo['user_id'],   //业务平台用户id
                        ];

                        try {
                            // 请求代扣接口
                            $withStatus = $withholding->deduct($withholding_data);

                            if( !isset($withStatus['status']) || $withStatus['status'] != 'processing'){

                                \App\Lib\Common\LogApi::error('[createpay]分期代扣错误,返回的结果及参数分别为：', [$withStatus,$withholding_data]);
                                OrderGoodsInstalment::instalment_failed($item['fail_num'], $item['id']);
                            }


                            LogApi::info('[crontabCreatepay]分期代扣返回：'.$subject.'：结果及调用的参数:', [$withStatus,$withholding_data]);
                        } catch (\Exception $exc) {
                            LogApi::error('[crontabCreatepay]分期代扣错误异常：'.$subject, $exc);
                            OrderGoodsInstalment::instalment_failed($item['fail_num'], $item['id']);
                            //捕获异常 买家余额不足
                            if ($exc->getMessage() == "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage() == "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                                LogApi::error('[crontabCreatepay]分期扣款余额不足：'.$subject);
                                continue;
                            }

                        }

                    }catch (\Exception $exc) {
                        LogApi::error('[crontabCreatepay]分期扣款异常：'.$subject, $exc);
                        continue;
                    }

                }

                //记录日志
                $logData = [
                    'order_no'      => $item['order_no'],
                    'action'        => '分期扣款',
                    'business_key'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,//此处用常量
                    'business_no'   => $business_no,
                    'goods_no'      => $item['goods_no'],
                ];
                $goodsLog = \App\Order\Modules\Repository\GoodsLogRepository::add($logData, true);
                if( !$goodsLog ){
                    LogApi::error('[crontabCreatepay]分期扣款记录分期操作日志失败：'.$subject, $logData);
                    continue;
                }

            }

            if($page < $totalpage){
                sleep($time);
            }
            ++$page;


        } ;


    }

    /**
     * 主动还款
     * @requwet Array
     * [
     *      'return_url'         => '', 【必须】 String 前端回调地址
     *      'instalment_id'      => '', 【必须】 Int    分期主键ID
     *      'channel'			 => '', 【必须】 Int    支付渠道
     *      'extended_params'    => '', 【可选】 array  支付扩展参数
     * ]
     * @return String url 前端支付URL
     */
    public function repayment(Request $request){
        $all     = $request->all();

        $rules = [
            'return_url'        => 'required',
            'instalment_id'     => 'required|int',
            'channel'           => 'required|int',
        ];

        // 参数过滤
        $validateParams = $this->validateParams($rules,$all);
        if ($validateParams['code'] != 0) {
            return apiResponse([], $validateParams['code']);
        }
        $params = $all['params'];
		
        // 判断分期状态
        $instalmentId   = $params['instalment_id'];
        // 渠道
        $channelId      = $params['channel'];
        // 扩展参数
        $extended_params= isset($params['extended_params'])?$params['extended_params']:[];
		
		// 微信支付，交易类型：JSAPI，redis读取openid
		if( $channelId == \App\Order\Modules\Repository\Pay\Channel::Wechat ){
			if( isset($extended_params['wechat_params']['trade_type']) && $extended_params['wechat_params']['trade_type']=='JSAPI' ){
				$_key = 'wechat_openid_'.$all['auth_token'];
				$openid = \Illuminate\Support\Facades\Redis::get($_key);
				if( $openid ){
					$extended_params['wechat_params']['openid'] = $openid;
				}
				//return apiResponse([], ApiStatus::CODE_71000, "参数错误");
			}
		}

        $instalmentKey = "instalmentWithhold_" . $instalmentId;
        // 频次限制计数
        redisIncr($instalmentKey, 300);

        // 查询分期信息
        $instalmentInfo = OrderGoodsInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            return apiResponse([], $instalmentInfo, ApiStatus::$errCodes[$instalmentInfo]);
        }


        // 生成交易码
        $business_no = createNo();
        // 扣款交易码
        if( $instalmentInfo['business_no'] == '' ){
            // 1)记录租机交易码
            $b = OrderGoodsInstalment::save(['id'=>$instalmentId],['business_no'=>$business_no]);
            if( $b === false ){
                Log::error("数据异常");
                return apiResponse([], ApiStatus::CODE_71000, "更新交易码错误");
            }
            $instalmentInfo['business_no'] = $business_no;
        }

        $business_no = $instalmentInfo['business_no'];


        //开启事务
        DB::beginTransaction();

		try{

			//分期状态
			if( $instalmentInfo['status'] != OrderInstalmentStatus::UNPAID && $instalmentInfo['status'] != OrderInstalmentStatus::FAIL){
				DB::rollBack();
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
				DB::rollBack();
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

			//优惠券信息
			if($youhui > 0){

				// 创建优惠券使用记录
				$couponData = [
					'coupon_id'         => $couponInfo['coupon_id'],
					'discount_amount'   => $couponInfo['youhui'],
					'business_type'     => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
					'business_no'       => $business_no,
				];
				\App\Order\Modules\Repository\OrderCouponRepository::add($couponData);
			}

			// 创建支付单
			$payData = [
				'userId'            => $instalmentInfo['user_id'],//用户ID
				'businessType'		=> \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,	// 业务类型
				'businessNo'		=> $business_no,	                // 业务编号
				'orderNo'		    => $instalmentInfo['order_no'],	// 订单号
				'paymentAmount'		=> $amount,	                    // Price 支付金额，单位：元
				'paymentFenqi'		=> '0',	// int 分期数，取值范围[0,3,6,12]，0：不分期
			];
			$payResult = \App\Order\Modules\Repository\Pay\PayCreater::createPayment($payData);
			//获取支付的url
			$url = $payResult->getCurrentUrl($channelId, [
				'name'=>'订单' .$orderInfo['order_no']. '分期'.$instalmentInfo['term'].'提前还款',
				'front_url' => $params['return_url'], //回调URL
				'extended_params' => $extended_params,// 扩展参数
				'ip' => $all['ip'], // 客户端IP
			]);

			// 提交事务
			DB::commit();
		} catch (\App\Lib\ApiException $ex) {
			DB::rollBack();
			LogApi::error('系统接口异常',$ex->getOriginalValue());
			return apiResponse([], ApiStatus::CODE_50000, "服务器繁忙，请稍候重试...");
		} catch (\Exception $ex) {
			DB::rollBack();
			LogApi::error('系统异常',$ex);
			return apiResponse([], ApiStatus::CODE_50000, "服务器繁忙，请稍候重试...");
		} 

		return apiResponse($url,ApiStatus::CODE_0);


    }







}
