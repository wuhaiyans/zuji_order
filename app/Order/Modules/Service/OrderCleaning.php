<?php
/**
 *    订单清算服务类
 *    author: heaven
 *    date : 2018-05-14
 */
namespace App\Order\Modules\Service;
use App\Lib\Payment\CommonFundAuthApi;
use App\Lib\Payment\CommonRefundApi;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\OrderPayRepository;
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
       if (empty($orderCleanData))  return apiResponseArray(ApiStatus::CODE_10101,$orderCleanData);
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
        return apiResponseArray(ApiStatus::CODE_0,$orderCleanList);

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
        return $success;

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
        return $success;

    }


    /**
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
        dd($orderCleanData);
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
        //需退款金额大于0，并且属于待退款状态，发起清算，退租金
        if ($orderCleanData['refund_amount']>0 && $orderCleanData['refund_status']== OrderCleaningStatus::refundUnpayed) {
            //根据业务编号查找支付相关数据
            $orderPayInfo = OrderPayRepository::getInfo($orderCleanData['business_no']);
            if (empty($orderPayInfo)) return false;
            $params = [
                'out_refund_no' => $orderCleanData['out_refund_no'], //订单系统退款码
                'payment_no'	=> $orderPayInfo['payment_no'], //业务系统支付码
                'amount'		=> $orderCleanData['refund_amount']*100, //支付金额
                'refund_back_url' => config('tripartite.API_INNER_URL').'/refundClean', //退款回调URL
            ];
            CommonRefundApi::apple($params);

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
        if ($orderCleanData['deposit_deduction_amount']>0 && $orderCleanData['deposit_deduction_status']== OrderCleaningStatus::depositDeductionStatusUnpayed) {
            //根据业务编号查找支付相关数据
            $orderPayInfo = OrderPayRepository::getInfo($orderCleanData['business_no']);
            if (empty($orderPayInfo)) return false;
            $freezePayParams = [

                'name'		=> OrderCleaningStatus::getBusinessTypeName($orderCleanData['business_type']).'索赔扣押金', //交易名称
         		'out_trade_no' => $orderCleanData['out_refund_no'], //业务系统授权码
         		'auth_no' => $orderPayInfo['fundauth_no'], //支付系统授权码
         		'amount' => $orderCleanData['deposit_deduction_amount']*100, //交易金额；单位：分
         		'back_url' => config('tripartite.API_INNER_URL').'/unfreezeAndPayClean', //押金转支付回调URL
         		'user_id' => $orderCleanData['user_id'], //用户id

            ];
            CommonFundAuthApi::unfreezeAndPay($freezePayParams);

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
        if ($orderCleanData['deposit_unfreeze_amount']>0 && $orderCleanData['deposit_unfreeze_status']== OrderCleaningStatus::depositUnfreezeStatusUnpayed) {
            //根据业务编号查找支付相关数据
            $orderPayInfo = OrderPayRepository::getInfo($orderCleanData['business_no']);
            if (empty($orderPayInfo)) return false;
            $unFreezeParams = [
         		'name'		=> OrderCleaningStatus::getBusinessTypeName($orderCleanData['business_type']).'解冻资金', //交易名称
         		'out_trade_no' => $orderCleanData['out_refund_no'], //订单系统交易码
         		'auth_no' => $orderPayInfo['fundauth_no'], //支付系统授权码
         		'amount' => $orderCleanData['deposit_unfreeze_amount']*100, //解冻金额 单位：分
         		'back_url' => config('tripartite.API_INNER_URL').'/unFreezeClean', //预授权解冻接口回调url地址
         		'user_id' => $orderCleanData['user_id'],//用户id
            ];
            CommonFundAuthApi::unfreeze($unFreezeParams);
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