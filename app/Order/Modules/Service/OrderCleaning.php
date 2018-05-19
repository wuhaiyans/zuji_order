<?php
/**
 *    订单清算服务类
 *    author: heaven
 *    date : 2018-05-14
 */
namespace App\Order\Modules\Service;
use App\Lib\Payment\CommonRefundApi;
use App\Order\Modules\Repository\OrderClearingRepository;
use App\Lib\ApiStatus;


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

        //查询清算表
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
         * ]
         */
        //
        //发起清算 解押金，退租金
        CommonRefundApi::apply();
        $success= OrderClearingRepository::orderCleanOperate($param);
        return $success;


    }


}