<?php
/**
 *    订单清算服务类
 *    author: heaven
 *    date : 2018-05-14
 */
namespace App\Order\Modules\Service;
use App\Lib\Common\LogApi;
use App\Lib\Payment\CommonFundAuthApi;
use App\Lib\Payment\CommonRefundApi;
use App\Lib\Payment\mini\MiniApi;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\MiniOrderRepository;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\OrderPayRepository;
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

       if (empty($orderCleanData))  return apiResponseArray(ApiStatus::CODE_31205,$orderCleanData);
        //根据订单号查询订单信息

        $orderInfo = OrderUserInfoRepository::getUserInfo(array('order_no'=>$orderCleanData['order_no'],'user_id'=>$orderCleanData['user_id']));
        if (empty($orderInfo))  return apiResponseArray(ApiStatus::CODE_31205,$orderInfo);
        $orderCleanData['order_info']   = [
            'order_no'=> $orderInfo[0]['order_no'],
            'mobile' => $orderInfo[0]['mobile'],
            'name' => $orderInfo[0]['name'],

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
        return $success  ?   ApiStatus::CODE_0 : ApiStatus::CODE_CODE_31203;

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
                    'refund_back_url' => config('tripartite.API_INNER_URL').'/refundClean', //退款回调URL
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
                    'back_url' => config('tripartite.API_INNER_URL').'/unfreezeAndPayClean', //押金转支付回调URL
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
                    'back_url' => config('tripartite.API_INNER_URL').'/unFreezeClean', //预授权解冻接口回调url地址
                    'user_id' => $orderCleanData['user_id'],//用户id
                ];
                $succss = CommonFundAuthApi::unfreeze($unFreezeParams);
                LogApi::info('预授权解冻接口返回', [$succss, $unFreezeParams]);
            }
            return true;

        } else {

            $miniOrderData = MiniOrderRepository::getMiniOrderInfo($orderCleanData['order_no']);
            if (empty($miniOrderData))
            {
                LogApi::info('没有找到芝麻订单号相关信息', $orderCleanData['order_no']);
                return false;
            }

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


}