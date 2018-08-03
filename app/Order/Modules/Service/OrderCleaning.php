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
use App\Order\Modules\Repository\OrderMiniRepository;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderPayRepository;
use App\Order\Modules\Repository\OrderUserAddressRepository;
use App\Order\Modules\Repository\OrderUserInfoRepository;
use App\Order\Modules\Repository\Pay\PayQuery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


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
                $orderCleanList['data'][$keys]['is_operate'] = in_array($values['status'],array(2,3,4)) ?? 0;
                //入账来源
                $channelData = Channel::getChannel($values['app_id']);
                $orderCleanList['data'][$keys]['app_id_name'] = $channelData['appid']['name'];

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
     * @return int 0：成功；非0：失败
     */
    public static function upOrderCleanStatus($param)
    {

        $success= OrderClearingRepository::upOrderCleanStatus($param);
        return $success  ?   ApiStatus::CODE_0 : ApiStatus::CODE_31202;


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
        DB::beginTransaction();
        try {
            LogApi::info(__method__.'[cleanAccount发起]财务发起退款，解除预授权的请求，请求参数：', $param);
            //查询清算表根据业务平台退款码out_refund_no
            $orderCleanData =  OrderClearingRepository::getOrderCleanInfo($param['params']);
            if ($orderCleanData['status']==OrderCleaningStatus::orderCleaningComplete || $orderCleanData['status']==OrderCleaningStatus::orderCleaning) {
                return apiResponseArray(31202,[],"扣款已经发起过，请不要重复发起请求，请稍等");
            }
            if (empty($orderCleanData)) return apiResponseArray(31202,[],"清算记录不存在");



            //更新清算状态为清算中
            $orderParam = [
                'clean_no' => $orderCleanData['clean_no'],
                'status' => OrderCleaningStatus::orderCleaning,
                'operator_uid' => isset($param['userinfo']['uid']) ? $param['userinfo']['uid']: '',
                'operator_username' => isset($param['userinfo']['username']) ? $param['userinfo']['username']: '',
                'operator_type' => isset($param['userinfo']['type']) ? $param['userinfo']['type']: '',
            ];
            if(!isset($param['userinfo']['uid'])) {
                unset($orderParam['operator_uid']);
            }
            if(!isset($param['userinfo']['username'])) {
                unset($orderParam['operator_username']);
            }

            if(!isset($param['userinfo']['type'])) {
                unset($orderParam['operator_type']);
            }
            $success = OrderCleaning::upOrderCleanStatus($orderParam);
            LogApi::info(__method__.'[cleanAccount发起]财务发起退款，更新清算状态为清算中，请求参数及结果：', [$orderParam,$success]);

            if ($success) return apiResponseArray(31202,[],"清算记录不存在");

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


                //需退款金额大于0，并且属于待退款状态，
                //发起清算，退租金
                if ($orderCleanData['refund_amount']>0 && $orderCleanData['refund_status']== OrderCleaningStatus::refundUnpayed
                    && (empty(floatval($orderCleanData['auth_deduction_amount'])) || $orderCleanData['auth_deduction_status']==OrderCleaningStatus::depositDeductionStatusPayd) && (empty(floatval($orderCleanData['auth_unfreeze_amount'])) ||  $orderCleanData['auth_unfreeze_status']==OrderCleaningStatus::depositUnfreezeStatusPayd)
                ) {

                    self::refundRequest($orderCleanData);
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
                if (empty($orderCleanData['auth_no'])) return false;
                $authInfo = PayQuery::getAuthInfoByAuthNo($orderCleanData['auth_no']);
                if ($orderCleanData['auth_deduction_amount']>0 && $orderCleanData['auth_deduction_status']== OrderCleaningStatus::depositDeductionStatusUnpayed) {
                    LogApi::info(__method__.'[cleanAccount发起]财务进入预授权转支付请求的逻辑');
                    if (!isset($authInfo['out_fundauth_no']) || empty($authInfo['out_fundauth_no'])) {
                        LogApi::error(__method__.'[cleanAccount发起]财务发起预授权转支付前，发现获取out_fundauth_no失败：', $authInfo);
                        return apiResponseArray(31202,[],"财务发起预授权转支付前，发现获取out_fundauth_no失败");
                    }
                    $freezePayParams = [

                        'name'		=> OrderCleaningStatus::getBusinessTypeName($orderCleanData['business_type']).'索赔扣押金', //交易名称
                        'out_trade_no' => $orderCleanData['auth_deduction_no'], //业务系统授权码
                        'fundauth_no' => $authInfo['out_fundauth_no'], //支付系统授权码
                        'amount' => $orderCleanData['auth_deduction_amount']*100, //交易金额；单位：分
                        'back_url' => config('ordersystem.ORDER_API').'/unfreezeAndPayClean', //押金转支付回调URL
                        'user_id' => $orderCleanData['user_id'], //用户id

                    ];
                    LogApi::info(__method__.'[cleanAccount发起]财务发起预授权转支付请求以前，请求的参数：',$freezePayParams);
                    $succss = CommonFundAuthApi::unfreezeAndPay($freezePayParams);
                    LogApi::info(__method__.'[cleanAccount发起]财务已经预授权转支付请求以后，返回的结果：',$succss);
                }

                //需解押金额大于0，并且属于待解押金状态，发起解押押金请求
                if ($orderCleanData['auth_unfreeze_amount']>0 && $orderCleanData['auth_unfreeze_status']== OrderCleaningStatus::depositUnfreezeStatusUnpayed
                    && (empty(floatval($orderCleanData['auth_deduction_amount'])) || $orderCleanData['auth_deduction_status']==OrderCleaningStatus::depositDeductionStatusPayd)) {
                    self::unfreezeRequest($orderCleanData);
                }

            } else {


                //小程序待退还押金大于0，并且处于待退押金状态
                if ($orderCleanData['auth_unfreeze_amount']>0 && $orderCleanData['auth_unfreeze_status']== OrderCleaningStatus::depositUnfreezeStatusUnpayed) {



                    $miniOrderData = OrderMiniRepository::getMiniOrderInfo($orderCleanData['order_no']);

                    if (empty($miniOrderData))
                    {
                        LogApi::error(__method__.'[cleanAccount发起]没有找到芝麻订单号相关信息', $orderCleanData['order_no']);
                        return apiResponseArray(31202,[],"没有找到芝麻订单号相关信息");
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
                            'app_id'=> $miniOrderData['app_id'],//芝麻小程序APPID
                        ];
                        $succss =  miniApi::OrderClose($params);
                        LogApi::info(__method__.'[cleanAccount发起]支付小程序解冻押金', [$succss,  $params]);
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
                            'app_id'=>  $miniOrderData['app_id'],//芝麻小程序APPID
                        ];
//                        dd($orderParams);
                        $success =  miniApi::OrderCancel($orderParams);
                        LogApi::info(__method__.'[cleanAccount发起]支付小程序解冻押金', [$success,  $orderParams]);
                    }

                }

            }
            DB::commit();
            return apiResponseArray(0,[],"成功");

        } catch (\Exception $e) {
            DB::rollback();
            LogApi::error(__method__.'[cleanAccount发起]操作请求异常',$e);
            return apiResponseArray(31202,[],"操作请求异常".$e->getMessage());

        }


    }


    /**
     *
     * 发起退款的请求
     * Author: heaven
     * @param $param
     * @param $orderCleanData  array 清算详情数组
     * @return bool
     * @throws \App\Lib\NotFoundException
     */
    public static function refundRequest($orderCleanData)
    {

        LogApi::info(__method__.'[cleanAccount发起]财务进入退款请求的逻辑');
        try{

            //查询清算表根据业务平台退款码out_refund_no
            if (empty($orderCleanData)) return false;

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
                //需退款金额大于0，并且属于待退款状态，
                //发起清算，退租金
                if ($orderCleanData['refund_amount'] > 0 && $orderCleanData['refund_status'] == OrderCleaningStatus::refundUnpayed) {


                    //根据支付编号查找支付相关数据
                    $payInfo = PayQuery::getPaymentInfoByPaymentNo($orderCleanData['payment_no']);

                    if (!isset($payInfo['out_payment_no']) || empty($payInfo['out_payment_no'])) {
                        LogApi::error(__method__.'[cleanAccount发起]财务发起退款申请前，发现out_payment_no失败：', $payInfo);
                        return false;
                    }
                    $params = [
                        'out_refund_no' => $orderCleanData['refund_clean_no'], //业务平台退款码
                        'payment_no' => $payInfo['out_payment_no'], //支付平台支付码
                        'amount' => $orderCleanData['refund_amount'] * 100, //支付金额
                        'refund_back_url' => config('ordersystem.ORDER_API') . '/refundClean', //退款回调URL
                    ];
                    LogApi::info(__method__.'[cleanAccount发起]财务发起退款请求前，请求的参数：', $params);
                    $succss = CommonRefundApi::apply($params);
                    LogApi::info(__method__.'[cleanAccount发起]财务已经发起退款请求，请求后的参数及结果：',$succss);

                }
            }

        } catch (\Exception $e) {
            LogApi::error(__method__.'[cleanAccount发起退款]操作请求异常',$e);
        }



    }


    /**
     * 发起解除预授权的请求
     * Author: heaven
     * @param $orderCleanData 清算详情数据 array
     * @return bool
     * @throws \App\Lib\NotFoundException
     */
    public static function unfreezeRequest($orderCleanData)
    {

        LogApi::info(__method__.'[cleanAccount发起]财务进入解除预授权请求的逻辑',$orderCleanData);

        try{

            if (empty($orderCleanData)) return false;

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

                //根据预授权编号查找预授权相关数据
                if (empty($orderCleanData['auth_no'])) return false;

                $authInfo = PayQuery::getAuthInfoByAuthNo($orderCleanData['auth_no']);

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
                    if (!isset($authInfo['out_fundauth_no']) || empty($authInfo['out_fundauth_no'])) {

                        LogApi::error(__method__.'[cleanAccount发起]财务发起预授权解除前，发现out_fundauth_no失败：', $authInfo);
                        return false;
                    }
                    $unFreezeParams = [
                        'name'		=> OrderCleaningStatus::getBusinessTypeName($orderCleanData['business_type']).'解冻资金', //交易名称
                        'out_trade_no' => $orderCleanData['auth_unfreeze_no'], //订单系统交易码
                        'fundauth_no' => $authInfo['out_fundauth_no'], //支付系统授权码
                        'amount' => $orderCleanData['auth_unfreeze_amount']*100, //解冻金额 单位：分
                        'back_url' => config('ordersystem.ORDER_API').'/unFreezeClean', //预授权解冻接口回调url地址
                        'user_id' => $orderCleanData['user_id'],//用户id
                    ];
                    LogApi::info(__method__.'[cleanAccount发起]财务发起预授权解冻请求前，请求的参数：', $unFreezeParams);
                    $succss = CommonFundAuthApi::unfreeze($unFreezeParams);
                    LogApi::info(__method__.'[cleanAccount发起]财务已经发起预授权解冻请求，请求后的请求及结果：', $succss);
                }

            }

        } catch (\Exception $e) {
            LogApi::error(__method__.'[cleanAccount发起预授权解除]操作请求异常',$e);
        }


    }


    /**
     *
     * 订单清算回调业务接口
     * Author: heaven
     * @return boolean  true：成功；false：失败
     */
    public static function getBusinessCleanCallback($businessType, $businessNo, $result, $userinfo=[])
    {
        $callbacks = config('pay_callback.refund');
        if( !isset($callbacks[$businessType]) || !$callbacks[$businessType] ){
			LogApi::error('[清算阶段]业务未设置回调通知');
			return false;
        }
        if( !is_callable($callbacks[$businessType]) ){
			LogApi::error('[清算阶段]业务回调通知不可调用');
			return false;
        }
		$params = [
			'business_type' => $businessType,
			'business_no' => $businessNo,
			'status' => $result
		];

		LogApi::debug('[清算阶段]业务回调通知',[
			'callback' => $callbacks[$businessType],
			'params' => $params,
		]);

		return call_user_func_array($callbacks[$businessType],[$params,$userinfo]);
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
            LogApi::info(__method__.'[cleanAccount小程序订单清算退押金回调接口回调参数:', $param);
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
                LogApi::error(__method__.'[cleanAccount小程序 订单清算记录不存在');
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
                    LogApi::info(__method__.'[cleanAccount小程序订单清算回调结果{$success}OrderCleaning::getBusinessCleanCallback业务接口回调参数:', $businessParam);
                    return $success ?? false;
                } else {

                        LogApi::error(__method__.'[cleanAccount小程序更新订单清算状态失败');
                        return false;
                     }
            } else {

                LogApi::error(__method__.'[cleanAccount小程序订单清算退款状态无效');
            }
            return true;

        } catch (\Exception $e) {
            LogApi::error(__method__.'[cleanAccount小程序订单清算押金转支付回调接口异常 ',$e);
            return false;

        }

    }


}