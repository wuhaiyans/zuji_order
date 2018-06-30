<?php
/**
 *    订单清算服务类
 *    author: heaven
 *    date : 2018-05-14
 */
namespace App\Order\Modules\Service;
use App\Lib\Channel\Channel;
use App\Lib\Common\LogApi;
use App\Lib\Payment\CommonFundAuthApi;
use App\Lib\Payment\CommonRefundApi;
use App\Lib\Payment\mini\MiniApi;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\MiniOrderRepository;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderPayRepository;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Repository\Pay\PayQuery;
use Illuminate\Support\Facades\Log;


class OrderCleaning
{



    /**
     * 订单清算详情
     * Author: heaven
     * @param $param
     * @return array
     */
    public static function getOrderCleanInfo($param)
    {

       $orderCleanData =  OrderClearingRepository::getOrderCleanInfo($param);

        $orderCleanData['order_type_name'] = OrderStatus::getTypeName($orderCleanData['order_type']);
        $orderCleanData['out_account_name'] = PayInc::getPayName($orderCleanData['out_account']);

       if (empty($orderCleanData))  return apiResponseArray(ApiStatus::CODE_31205,$orderCleanData);
        //根据订单号查询订单信息

        $orderInfo = OrderUserAddressRepository::getUserAddressInfo(array('order_no'=>$orderCleanData['order_no']));
        if (empty($orderInfo))  return apiResponseArray(ApiStatus::CODE_31205,$orderInfo);
        $orderCleanData['order_info']   = [
            'order_no'=> $orderInfo['order_no'],
            'consignee_mobile' => $orderInfo['consignee_mobile'],
            'name' => $orderInfo['name'],

        ];
        return apiResponseArray(ApiStatus::CODE_0,$orderCleanData);

    }


    /**
     * 订单清算列表
     * Author: heaven
     * @param array $param
     * @return array
     */
    public static function getOrderCleaningList($param = array())
    {
        $orderCleanList = OrderClearingRepository::getOrderCleanList($param);
        if (!empty($orderCleanList['data'])) {

            foreach($orderCleanList['data'] as $keys=>$values){
                $orderCleanList['data'][$keys]['order_type_name'] = OrderStatus::getTypeName($values['order_type']);
                $orderCleanList['data'][$keys]['out_account_name'] = PayInc::getPayName($values['out_account']);
                //dd(OrderCleaningStatus::getOrderCleaningName($values['status']));
                $orderCleanList['data'][$keys]['status_name'] = OrderCleaningStatus::getOrderCleaningName($values['status']);
                //入账来源
                $channelData = Channel::getChannel($values['app_id']);
                $orderCleanList['data'][$keys]['app_id_name'] = $channelData['name'];

            }


        }
        return $orderCleanList;
//        return apiResponseArray(ApiStatus::CODE_0,$orderCleanList);

    }



    /**
     * 订单清算取消
     * Author: heaven
     * @param array $param
     * @return bool
     */
    public static function cancelOrderClean($param = array())
    {
        $success= OrderClearingRepository::cancelOrderClean($param);
        return $success  ?   ApiStatus::CODE_0 : ApiStatus::CODE_31203;

    }


    /**
     * 更新订单清算状态
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function upOrderCleanStatus($param)
    {

        $success= OrderClearingRepository::upOrderCleanStatus($param);
        return $success  ?   ApiStatus::CODE_0 : ApiStatus::CODE_CODE_31202;


    }


    /**
     * 传参注释：
     * [
     *
     *      order_no  订单编号  ：必填
     *      business_type 业务类型：必填
     *      business_no 业务编号：必填
     *      order_type   1线上订单2门店订单 3小程序订单：必填
     *      out_auth_no  1.需要退预授权的钱或者预授权的钱转支付必填 ,2，没有预授权或者预授权金额为0，此参数不用传    ：选填
     *      out_payment_no 需要退款必填       2，退款金额为0，此参数不用传 ：选填
     *      auth_deduction_amount  预授权转支付金额：如果为0不用传 ：选填
     *      auth_unfreeze_amount   解除预授权的金额: 如果为0不用传    ：选填
     *      refund_amount          退款金额：如果为0:不用传    ：选填
     *
     *  ]
     *
     *
     * 插入订单清算
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function createOrderClean($param)
    {

        $success= OrderClearingRepository::createOrderClean($param);
        return $success;


    }





    /**
     * 订单清算操作
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function orderCleanOperate($param)
    {
        //查询清算表根据业务平台退款码out_refund_no

        $orderCleanData =  OrderClearingRepository::getOrderCleanInfo($param);

        if (empty($orderCleanData)) return false;

        //更新清算状态为支付中
        $orderParam = [
            'clean_no' => $orderCleanData['clean_no'],
            'status' => OrderCleaningStatus::orderCleaning
        ];
        $success = OrderCleaning::upOrderCleanStatus($orderParam);

        if (!$success) return false;
        /**
         * 退款申请接口
         * @param array $params
         * [
         *		'out_refund_no' => '', //订单系统退款码
         *		'payment_no'	=> '', //业务系统支付码
         *		'amount'		=> '', //支付金额
         *		'refund_back_url' => '', //退款回调URL
         * ]
         * @return mixed false：失败；array：成功
         * [
         * 		'out_refund_no'=>'', //订单系统退款码
         * 		'refund_no'=>'', //支付系统退款码
         *       'status'
         * ]
         */
        if ($orderCleanData['order_type']!=OrderStatus::orderMiniService) {

            //需退款金额大于0，并且属于待退款状态，发起清算，退租金
            if ($orderCleanData['refund_amount']>0 && $orderCleanData['refund_status']== OrderCleaningStatus::refundUnpayed) {

                //根据支付编号查找支付相关数据
                $payInfo = PayQuery::getPaymentInfoByPaymentNo($orderCleanData['payment_no']);
                if (!isset($payInfo['out_payment_no']) || empty($payInfo['out_payment_no'])) {

                    return false;
                    LogApi::info('PayQuery::getPayByPaymentNo获取失败,参数：{$orderCleanData[\'payment_no\']}', $payInfo);
                }
                $params = [
                    'out_refund_no' => $orderCleanData['clean_no'], //业务平台退款码
                    'payment_no'	=> $payInfo['out_payment_no'], //支付平台支付码
                    'amount'		=> $orderCleanData['refund_amount']*100, //支付金额
                    'refund_back_url' => config('tripartite.ORDER_API').'/refundClean', //退款回调URL
                ];
                $succss =  CommonRefundApi::apply($params);
                LogApi::info('退款申请接口返回', [$succss, $params]);

            }

            //需扣除金额大于0，并且属于待扣押金状态，发起带扣押金请求
            /**
             * 预授权转支付接口
             * @param string $appid		应用ID
             * @param array $params
             * [
             *		'name'		=> '', //交易名称
             *		'out_trade_no' => '', //业务系统授权码
             *		'auth_no' => '', //支付系统授权码
             *		'amount' => '', //交易金额；单位：分
             *		'back_url' => '', //后台通知地址
             *		'user_id' => '', //用户id
             * ]
             * @return mixed false：失败；array：成功
             * [
             *		'out_trade_no' => '',//支付系统交易码
             *		'trade_no' => '',//业务系统交易码
             *		'out_auth_no' => '',//支付系统授权码
             * ]
             */
            //根据预授权编号查找预授权相关数据
            if (empty($orderCleanData['auth_no'])) return true;
            $authInfo = PayQuery::getAuthInfoByAuthNo($orderCleanData['auth_no']);
            if (!isset($authInfo['out_fundauth_no']) || empty($authInfo['out_fundauth_no'])) {

                return false;
                LogApi::info('PayQuery::getPayByFundauthNo获取失败,参数：{$orderCleanData[\'auth_no\']}', $authInfo);
            }
            if ($orderCleanData['auth_deduction_amount']>0 && $orderCleanData['auth_deduction_status']== OrderCleaningStatus::depositDeductionStatusUnpayed) {
                $freezePayParams = [

                    'name'		=> OrderCleaningStatus::getBusinessTypeName($orderCleanData['business_type']).'索赔扣押金', //交易名称
                    'out_trade_no' => $orderCleanData['clean_no'], //业务系统授权码
                    'fundauth_no' => $authInfo['out_fundauth_no'], //支付系统授权码
                    'amount' => $orderCleanData['auth_deduction_amount']*100, //交易金额；单位：分
                    'back_url' => config('tripartite.ORDER_API').'/unfreezeAndPayClean', //押金转支付回调URL
                    'user_id' => $orderCleanData['user_id'], //用户id

                ];
                $succss = CommonFundAuthApi::unfreezeAndPay($freezePayParams);
                LogApi::info('预授权转支付接口返回', [$succss,$freezePayParams]);
            }

            //需解押金额大于0，并且属于待解押金状态，发起解押押金请求
            /**
             * 预授权解冻接口
             * @param array $params
             * [
             *		'name'		=> 解冻资金, //交易名称
             *		'out_trade_no' => '', //订单系统交易码
             *		'auth_no' => '', //支付系统授权码
             *		'amount' => '', //解冻金额 单位：分
             *		'back_url' => '', //后台通知地址
             *		'user_id' => '', //用户id
             * ]
             * @return mixed false：失败；array：成功
             * [
             *		'out_trade_no' => '',//支付系统交易码
             *		'trade_no' => '',//业务系统交易码
             *		'out_auth_no' => '',//支付系统授权码
             * ]
             */
            if ($orderCleanData['auth_unfreeze_amount']>0 && $orderCleanData['auth_unfreeze_status']== OrderCleaningStatus::depositUnfreezeStatusUnpayed) {
                $unFreezeParams = [
                    'name'		=> OrderCleaningStatus::getBusinessTypeName($orderCleanData['business_type']).'解冻资金', //交易名称
                    'out_trade_no' => $orderCleanData['clean_no'], //订单系统交易码
                    'fundauth_no' => $authInfo['out_fundauth_no'], //支付系统授权码
                    'amount' => $orderCleanData['auth_unfreeze_amount']*100, //解冻金额 单位：分
                    'back_url' => config('tripartite.ORDER_API').'/unFreezeClean', //预授权解冻接口回调url地址
                    'user_id' => $orderCleanData['user_id'],//用户id
                ];
                $succss = CommonFundAuthApi::unfreeze($unFreezeParams);
                LogApi::info('预授权解冻接口返回', [$succss, $unFreezeParams]);
            }


        } else {

            //小程序待退还押金大于0，并且处于待退押金状态
            if ($orderCleanData['auth_unfreeze_amount']>0 && $orderCleanData['auth_unfreeze_status']== OrderCleaningStatus::depositUnfreezeStatusUnpayed) {

                $miniOrderData = MiniOrderRepository::getMiniOrderInfo($orderCleanData['order_no']);
                if (empty($miniOrderData))
                {
                    LogApi::info('没有找到芝麻订单号相关信息', $orderCleanData['order_no']);
                    return false;
                }

                //查询分期有没有代扣并且扣款成功的记录
                $instaleCount =  OrderGoodsInstalmentRepository::queryCount(['order_no'=>$orderCleanData['order_no'], 'status'=>OrderInstalmentStatus::SUCCESS, 'pay_type'=>0]);
                if ($instaleCount>0) {
                    $params = [
                        'out_order_no'=>$orderCleanData['order_no'],//商户端订单号
                        'zm_order_no'=>$miniOrderData['zm_order_no'],//芝麻订单号
                        'out_trans_no'=>$orderCleanData['clean_no'],//资金交易号
                        'pay_amount'=>$orderCleanData['auth_deduction_amount'],//支付金额
                        'remark'=>'小程序退押金',//订单操作说明
                        'app_id'=> config('MiniApi.ALIPAY_MINI_APP_ID'),//芝麻小程序APPID
                    ];
                    $succss =  miniApi::OrderClose($params);
                    LogApi::info('支付小程序解冻押金', [$succss,  $params]);
                } else {
                    /*
                      * 订单取消
                      * params [
                      *      'out_order_no'=>'',//商户端订单号
                      *      'zm_order_no'=>'',//芝麻订单号
                      *      'app_id'=>'',//芝麻小程序APPID
                      * ]
                      */
                    $orderParams = [
                        'out_order_no'=>$orderCleanData['order_no'],//商户端订单号
                        'zm_order_no'=>$miniOrderData['zm_order_no'],//芝麻订单号
                        'app_id'=>config('MiniApi.ALIPAY_MINI_APP_ID'),//芝麻小程序APPID
                    ];
                    $success =  miniApi::OrderCancel($orderParams);
                    LogApi::info('支付小程序解冻押金', [$success,  $orderParams]);
                }

            }

        }


        return true;

    }





    /**
     *
     * 订单清算回调业务接口
     * Author: heaven
     * @return mixed
     */
    public static function getBusinessCleanCallback($businessType, $businessNo, $result)
    {
        $callbacks = config('pay_callback.refund');
        if( isset($callbacks[$businessType]) && $callbacks[$businessType] ){

            $params = [
                'business_type' => $businessType,
                'business_no' => $businessNo,
                'status' => $result
            ];

            return call_user_func_array($callbacks[$businessType],$params);
        }
        Log::error('[清算阶段]业务未设置回调通知');
    }

    /**
     * 订单清算小程序回调接口
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function miniUnfreezeAndPayClean($param)
    {
        try{
            LogApi::info(__METHOD__.'() '.microtime(true).'订单清算退押金回调接口回调参数:', $param);
            /**
            支付宝小程序解压预授权成功后返回的值
            pay_amount（单位元）
            out_order_no商户订单号
            zm_order_no芝麻订单号
            notify_type 订单完结类型，目前包括取消(ZM_RENT_ORDER_CANCEL)、完结(ZM_RENT_ORDER_FINISH)
            ZM_RENT_ORDER_CANCEL 一般不会有out_trans_no和alipay_fund_no，为ZM_RENT_ORDER_FINISH时才有out_trans_no和alipay_fund_no
            out_trans_no 资商户资金交易号，最长32位，数字和字母组合，同一笔交易金额以最后一次的交易金额为准（请求时传入才会返回，扣款预授权支付金额为0.00则不需要传）
            alipay_fund_no支付成功时支付宝生成的资金流水号，用于商户与支付宝进行对账（请求时传入out_trans_no才会返回）
             * */
            if (!isset($param['out_order_no'])) return false;
            //更新查看清算表的状态
            $orderCleanInfo = OrderCleaning::getOrderCleanInfo(['order_no'=>$param['out_order_no'], 'order_type'=>OrderStatus::orderMiniService]);
            if ($orderCleanInfo['code']) {
                LogApi::info(__METHOD__."() ".microtime(true)." 订单清算记录不存在");
                return false;
            }
            $orderCleanInfo = $orderCleanInfo['data'];
            //查看清算状态是否已支付
            if ($orderCleanInfo['auth_unfreeze_status']==OrderCleaningStatus::depositDeductionStatusUnpayed){

                //更新订单清算押金转支付状态
                $orderParam = [
                    'order_no' => $param['out_order_no'],
                    'out_unfreeze_pay_trade_no'     => $param['alipay_fund_no'] ?? '',
                ];
                $success = OrderClearingRepository::upMiniOrderCleanStatus($orderParam);
                if (!$success) {
                    //更新业务系统的状态
                    $businessParam = [
                        'business_type' => $orderCleanInfo['business_type'],	// 业务类型
                        'business_no'	=> $orderCleanInfo['business_no'],	// 业务编码
                        'status'		=> 'success',	// 支付状态  processing：处理中；success：支付完成
                    ];
                    $success =  OrderCleaning::getBusinessCleanCallback($businessParam['business_type'], $businessParam['business_no'], $businessParam['status']);
                    LogApi::info(__METHOD__.'() '.microtime(true).'小程序订单清算回调结果{$success}OrderCleaning::getBusinessCleanCallback业务接口回调参数:', $businessParam);
                    return $success ?? false;
                } else {

                        LogApi::info(__METHOD__."() ".microtime(true)." 更新订单清算状态失败");
                        return false;
                     }
            } else {

                LogApi::info(__METHOD__ . "() " . microtime(true) . " {$param['out_refund_no']}订单清算退款状态无效");
            }
            return true;

        } catch (\Exception $e) {
            LogApi::info(__METHOD__ . "()订单清算押金转支付回调接口异常 " .$e->getMessage(),$param);
            return false;

        }

    }


}