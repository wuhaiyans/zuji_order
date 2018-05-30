<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;

use App\Order\Modules\Repository\OrderInstalmentRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class OrderInstalment
{

    /**
     * 创建订单分期
     * @return array
     *  $array = [
    'order'=>[
    'order_no'=>1,
    ],
    'sku'=>[
    'zuqi'=>1,
    'zuqi_type'=>1,
    'all_amount'=>1,
    'amount'=>1,
    'yiwaixian'=>1,
    'zujin'=>1,
    'yiwaixian'=>1,
    'payment_type_id'=>1,
    ],
    'coupon'=>[
    'discount_amount'=>1,
    'coupon_type'=>1,
    ],
    'user'=>[
    'withholding_no'=>1,
    ],
    ];
     */
    public static function create($params){
        $order    = $params['order'];
        $params['sku']      = $params['sku'][0];
        $sku      = $params['sku'];
        $coupon   = isset($params['coupon']) ? $params['coupon'] : "";
        $user     = $params['user'];

        $order = filter_array($order, [
            'order_no' => 'required',
        ]);
        if(!$order['order_no']){
            return false;
        }

        //获取sku
        $sku = filter_array($sku, [
            'goods_no'      => 'required',
            'zuqi'          => 'required',
            'zuqi_type'     => 'required',
            'all_amount'    => 'required',
            'amount'        => 'required',
            'yiwaixian'     => 'required',
            'zujin'         => 'required',
            'pay_type'      => 'required',
            'buyout_price'  => 'required',
        ]);

        if(count($sku) < 8){
            return false;
        }

        filter_array($coupon, [
            'discount_amount'   => 'required',
            'coupon_type'       => 'required',
        ]);


        $user = filter_array($user, [
            'user_id'        => 'required',
            'withholding_no' => 'required',
        ]);
        if(count($user) < 2){
            return false;
        }

        $res = new OrderInstalmentRepository($params);
        return $res->create();

    }


    /**
     * 创建订单分期
     * @return array
     *  $array = [
    'order'=>[
    'order_no'=>1,
    ],
    'sku'=>[
    'zuqi'=>1,
    'zuqi_type'=>1,
    'all_amount'=>1,
    'amount'=>1,
    'yiwaixian'=>1,
    'zujin'=>1,
    'yiwaixian'=>1,
    'payment_type_id'=>1,
    ],
    'coupon'=>[
    'discount_amount'=>1,
    'coupon_type'=>1,
    ],
    'user'=>[
    'withholding_no'=>1,
    ],
    ];
     */
    public static function get_data_schema($params){
        $params['sku']=$params['sku'][0];
        $sku      = $params['sku'];
        $coupon   = !empty($params['coupon']) ? $params['coupon'] : "";
        $user     = $params['user'];

        $sku = filter_array($sku, [
            'zuqi'=>'required',
            'zuqi_type'=>'required',
            'all_amount'=>'required',
            'amount'=>'required',
            'yiwaixian'=>'required',
            'zujin'=>'required',
            'pay_type'=>'required',
        ]);

        if(count($sku) < 7){
            return false;
        }

        filter_array($coupon, [
            'discount_amount'=>'required',
            'coupon_type'=>'required',
        ]);

        $user = filter_array($user, [
            'withholding_no' => 'required',
        ]);
        if(count($user) < 1){
            return false;
        }

        $res = new OrderInstalmentRepository($params);
        return $res->get_data_schema();


    }

    /**
     * 查询分期数据
     * @params array 查询条件
     * @return array
     */
    public static function queryInfo($params){
        if (empty($params)) {
            return ApiStatus::CODE_20001;
        }

        $result =  OrderInstalmentRepository::getInfo($params);
        if(!$result){
            return ApiStatus::CODE_71001;
        }
        return $result;
    }

    /**
     * 根据InstalmentId查询分期数据
     * @return array
     */
    public static function queryByInstalmentId($id){
        if (empty($id)) {
            return ApiStatus::CODE_20001;
        }

        $result =  OrderInstalmentRepository::getInfoById($id);
        if(!$result){
            return ApiStatus::CODE_71001;
        }
        return $result;
    }


    /**
     * 查询分期数据
     * @return array
     */
    public static function queryList($params = [],$additional = []){
        if (!is_array($params)) {
            return ApiStatus::CODE_20001;
        }

        $params = filter_array($params, [
            'goods_no'  =>'required',
            'order_no'  =>'required',
            'status'    => 'required',
            'mobile'    => 'required',
            'term'      => 'required',
        ]);

        $additional = filter_array($additional, [
            'page'  =>'required',
            'limit'  =>'required',
        ]);

        $total = OrderInstalmentRepository::queryCount($params);
        if($total == 0){
            return [];
        }

        $result =  OrderInstalmentRepository::queryList($params, $additional);
        $result = array_group_by($result,'goods_no');
        $result['total'] = $total;

        return $result;
    }


    /**
     * 根据用户id和订单号、商品编号，关闭用户的分期
     * @param data  array
     * [
     *      'id'       => '', 主键ID
     *      'order_no' => '', 订单编号
     *      'goods_no' => '', 商品编号
     *      'user_id'  => ''  用户id
     * ]
     */
    public static function close($data){
        if (!is_array($data) || $data == [] ) {
            return false;
        }
        $result =  OrderInstalmentRepository::closeInstalment($data);
        return $result;
    }

    /**
     * 是否允许扣款
     * @param  int  $instalment_id 订单分期付款id
     * @return bool true false
     */
    public static function allowWithhold($instalment_id){
        if(empty($instalment_id)){
            return false;
        }
        $alllow = false;
        $instalment_info = OrderInstalmentRepository::getInfoById($instalment_id);

        $status = $instalment_info['status'];

        $year   = date("Y");
        $month  = intval(date("m"));
        if($month < 10 ){
            $month = "0".$month;
        }
        $term 	= $year.$month;
        $day 	= intval(date("d"));

        //查询订单记录
        $order_info = OrderRepository::getInfoById($instalment_info['order_no']);

        if($status == OrderInstalmentStatus::UNPAID || $status == OrderInstalmentStatus::FAIL){
            // 本月15后以后 可扣当月 之前没有扣款的可扣款
            if(($term == $instalment_info['term'] && $day >= 15) || $term > $instalment_info['term']){
                //判断订单状态 必须是租用中 或者完成关闭的状态 才允许扣款
                if($order_info['order_status'] == OrderStatus::OrderInService && $order_info['freeze_type'] == OrderFreezeStatus::Non){
                    $alllow = true;
                }
            }
        }
        return $alllow;
    }


    /**
     * 更新分期扣款的租机交易码
     * @param int $id	主键ID
     * @param string $trade_no	交易码
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function set_trade_no($id, $trade_no){
        if(!$id){
            return ApiStatus::CODE_20001;
        }

        if(!$trade_no){
            return ApiStatus::CODE_20001;
        }

        return OrderInstalmentRepository::setTradeNo($id, $trade_no);

    }

    /**
     * 更新分期扣款的租机交易码
     * @param int $id	主键ID
     * @param string $trade_no	交易码
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function instalment_failed($fail_num,$instalment_id,$term,$data_sms){

        $data_sms = filter_array($data_sms, [
            'mobile' => 'required',
            'orderNo' => 'required',
            'realName' =>'required',
            'goodsName' =>'required',
            'zuJin' =>'required',
        ]);
        if( count($data_sms) != 5 ){
            Log::error('短信参数错误');
            return false;
        }

        if ($fail_num == 0) {
            $model = 'hsb_sms_99a6f';
        } elseif ($fail_num > 0 && $term == date("Ym")) {
            $model = 'hsb_sms_16f75';
        } elseif ($fail_num > 0 && $term <= date("Ym") - 1) {
            $model = 'hsb_sms_7326b';
        }

        SmsApi::sendMessage($data_sms['mobile'], $model, [
            'realName'      => $data_sms['realName'],
            'orderNo'       => $data_sms['orderNo'],
            'goodsName'     => $data_sms['goodsName'],
            'zuJin'         => $data_sms['zuJin'],
            'serviceTel'    =>env("CUSTOMER_SERVICE_PHONE"),
        ]);

        $fail_num = intval($fail_num) + 1;

        //修改失败次数
        $b = OrderInstalmentRepository::save(['id'=>$instalment_id],['fail_num'=>$fail_num]);
        Log::error('更新失败次数失败');
        return $b;
    }


    /**
     * 修改方法
     * @param string $params 条件
     * @param string $data	 参数数组
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function save($params, $data){
        if (!is_array($params) || $data == [] ) {
            return false;
        }
        $result =  OrderInstalmentRepository::save($params, $data);
        return $result;
    }

    /**
     * 冻结分期
     * @param string $goods_no 商品单号
     * @return bool
     */
    public static function instalment_unfreeze($goods_no){
        if ( !$goods_no ) {
            return false;
        }
        $where = [
            'goods_no' => $goods_no,
        ];
        $result =  OrderInstalmentRepository::save($where, ['unfreeze_status'=>0,'status'=>OrderInstalmentStatus::CANCEL]);
        return $result;
    }

    /**
     * 用户还机代扣扣款
     * @param  int $instalment_id 分期ID
     * @return bool
     */
    public static function instalment_withhold($instalmentId ){
        if ( $instalmentId == "") {
            return false;
        }

        $remark         = "还机代扣剩余分期";
        //开启事务
        DB::beginTransaction();

        // 查询分期信息
        $instalmentInfo = OrderInstalment::queryByInstalmentId($instalmentId);
        if( !is_array($instalmentInfo)){
            DB::rollBack();
            // 提交事务
            return false;
        }

        // 状态在支付中或已支付时，直接返回成功
        if( $instalmentInfo['status'] == OrderInstalmentStatus::SUCCESS && $instalmentInfo['status'] = OrderInstalmentStatus::PAYING ){
            return true;
        }

        // 扣款交易码
        if( $instalmentInfo['trade_no'] == '' ){
            // 1)记录租机交易码
            $b = OrderInstalment::set_trade_no($instalmentId, createNo());
            if( $b === false ){
                DB::rollBack();
                return false;
            }

        }

        $tradeNo = $instalmentInfo['trade_no'];

        // 订单
        //查询订单记录
        $orderInfo = OrderRepository::getInfoById($instalmentInfo['order_no']);
        if( !$orderInfo ){
            DB::rollBack();
            return false;
        }

        // 查询用户协议
        $withholdInfo = OrderPayWithhold::find($orderInfo['user_id']);
        if(empty($withholdInfo)){
            DB::rollBack();
            return false;
        }

        $userInfo = \App\Lib\User\User::getUser($orderInfo['user_id']);
        if( !is_array($userInfo )){
            DB::rollBack();
            return false;
        }

        // 保存 备注，更新状态
        $data = [
            'remark' => $remark,
            'status' => OrderInstalmentStatus::PAYING,// 扣款中
        ];
        $result = OrderInstalmentRepository::save(['id'=>$instalmentId],$data);
        if(!$result){
            DB::rollBack();
            return false;
        }
        // 商品
        $subject = '订单-'.$instalmentInfo['order_no'].'-'.$instalmentInfo['goods_no'].'-第'.$instalmentInfo['times'].'期扣款';

        // 价格 元转化分
        $amount = $instalmentInfo['amount'] * 100;
        if( $amount < 0 ){
            DB::rollBack();
            return false;
        }


        $orderGoods = New \App\Order\Modules\Service\OrderGoods();
        $goodsInfo  = $orderGoods->getGoodsInfo($instalmentInfo['goods_no']);
        if(!$goodsInfo){
            return false;
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
        if( $orderInfo['pay_type'] == \App\Order\Modules\Inc\PayInc::MiniAlipay ){
            //获取订单的芝麻订单编号
            $miniOrderInfo = \App\Order\Modules\Repository\MiniOrderRentNotifyRepository::getMiniOrderRentNotify( $instalmentInfo['order_no'] );
            if( empty($miniOrderInfo) ){
                \App\Lib\Common\LogApi::info('本地小程序确认订单回调记录查询失败',$orderInfo['order_no']);
                return false;
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
                return true;
            }elseif($pay_status =='PAY_FAILED'){
                OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                return false;
            }elseif($pay_status == 'PAY_INPROGRESS'){
                return false;
            }else{
                return false;
            }
        }else {
            // 支付宝用户的user_id
            $alipayUserId = $withholdInfo['out_withhold_no'];
            if (!$alipayUserId) {
                DB::rollBack();
                return false;
            }

            // 代扣协议编号
            $agreementNo = $withholdInfo['withhold_no'];
            if (!$agreementNo) {
                DB::rollBack();
                return false;
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

            try{
                // 请求代扣接口
                $withholding->deduct($withholding_data);
            }catch(\Exception $exc){
                DB::rollBack();
                \App\Lib\Common\LogApi::error('分期代扣错误', $withholding_data);
                //捕获异常 买家余额不足
                if ($exc->getMessage()== "BUYER_BALANCE_NOT_ENOUGH" || $exc->getMessage()== "BUYER_BANKCARD_BALANCE_NOT_ENOUGH") {
                    OrderInstalment::instalment_failed($instalmentInfo['fail_num'], $instalmentId, $instalmentInfo['term'], $dataSms);
                    return false;
                } else {
                    return false;
                }
            }

            //发送手机短信
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

        DB::commit();
        return true;
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
    public function repaymentNotify($params){

        $rules = [
            'payment_no'  => 'required',
            'out_no'      => 'required',
            'status'      => 'required',
            'reason'      => 'required',
        ];
        $validator = app('validator')->make($params, $rules);
        if ($validator->fails()) {
            set_apistatus(ApiStatus::CODE_20001, $validator->errors()->first());
            return false;
        }

        $trade_no = $params['out_no'];

        if($params['status'] == "success"){
            //修改分期状态
            $b = OrderInstalment::save(['trade_no'=>$trade_no],['status'=>OrderInstalmentStatus::SUCCESS]);
            if(!$b){
                echo "FAIL";exit;
            }
        }else{
            // 支付失败 恢复优惠券
            $where = [
                'business_type'     => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
                'business_no'       => $trade_no,
            ];
            $couponInfo = \App\Order\Modules\Repository\OrderCouponRepository::find($where);
            if(!empty($couponInfo)){
                $instalmentInfo     = $this->queryInfo(['trade_no'=>$trade_no]);
                $arr = [
                    'user_id'       => $instalmentInfo['user_id'],
                    'coupon_id'     => $couponInfo['coupon_id'],
                ];
                \App\Lib\Coupon\Coupon::setCoupon([],$arr);

            }

            LogApi::info('支付异步通知', $params);
        }

        echo "SUCCESS";

    }

}