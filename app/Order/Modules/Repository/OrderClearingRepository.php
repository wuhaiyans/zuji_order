<?php
/**
 *  订单清算数据处理
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/5/14 0018
 * Time: 下午 3:18
 */
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Models\OrderClearing;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderRepository;

class OrderClearingRepository
{



    /**
     * 退款结算数据录入
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function createOrderClean($param){
        if ( empty($param) ) {
            return false;
        }

        $orderClearData = new OrderClearing();
        //根据订单号查询订单信息
        if(isset($param['order_no'])){
            $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$param['order_no']));
            if (empty($orderInfo)) {
                return false;
            }
            if ($orderInfo['pay_type'] == PayInc::LebaifenPay) {

                $param['order_type'] = OrderStatus::miniRecover;
            }
        }

        $authDeductionNo    =   0;
        $authUnfreezeNo     =   0;
        $refundCleanNo      =   0;
        //是否是押金转支付
        $isAuthDeduction    =   0;
        //是否是解预授权
        $isAuthUnfreeze     =   0;
        //是否是退款
        $isRefund           =   0;
        if (isset($param['auth_deduction_amount'])  && floatval($param['auth_deduction_amount'])>0) {
            $isAuthDeduction = 1;
        }

        if (isset($param['auth_unfreeze_amount'])  && floatval($param['auth_unfreeze_amount'])>0) {
            $isAuthUnfreeze = 1;
        }

        if (isset($param['refund_amount'])  &&  floatval($param['refund_amount'])>0) {
            $isRefund   =   1;
        }

        if ($isAuthDeduction) {
            $authDeductionStatus = OrderCleaningStatus::depositDeductionStatusUnpayed;
            $authDeductionNo    = createNo('AD');
        }
        if ($isAuthUnfreeze)    {
            $authUnfreezeStatus = OrderCleaningStatus::depositDeductionStatusUnpayed;
            $authUnfreezeNo    = createNo('AU');
        }
        if ($isRefund) {
            $authRefundStatus = OrderCleaningStatus::depositDeductionStatusUnpayed;
            $refundCleanNo    = createNo('RC');
        }

        //预授权转支付，预授权解押，退款金额全为空，清算状态设为已完成
        if (empty($isAuthDeduction) && empty($isAuthUnfreeze) && empty($isRefund))
        {
            $status    =   OrderCleaningStatus::orderCleaningComplete;
            //预授权转支付不为空，为待押金转支付状态
        } else if ($isAuthDeduction){

            $status    =   OrderCleaningStatus::orderCleaningDeposit;

            //预授权转支付不为空，为待押金转支付状态
        }   else if (empty($isAuthDeduction) && !empty($isAuthUnfreeze)){

            $status    =   OrderCleaningStatus::orderCleaningUnfreeze;

        }   else if (empty($isAuthDeduction) && empty($isAuthUnfreeze) && $isRefund){

            $status    =   OrderCleaningStatus::orderCleaningUnRefund;
        }

       // if (empty($orderInfo)) return false;
        //if(redisIncr($param['order_no'].'_orderCleaning_create',60)>1) {
       //     return false;
      //  }
        
        if(redisIncr($param['business_no'].'_orderCleaning_create',60)>1) {
            return false;
         }

        //小程序如果是穿已经完成的状态，将其它状态也全部变为已完成的状态
        if (isset($param['status']) && $param['status']==OrderCleaningStatus::orderCleaningComplete) {

            $status    =   OrderCleaningStatus::orderCleaningComplete;

            $authDeductionStatus = OrderCleaningStatus::depositDeductionStatusPayd;

            $authUnfreezeStatus = OrderCleaningStatus::depositUnfreezeStatusPayd;

            $authRefundStatus = OrderCleaningStatus::refundPayd;

        }


        // 创建结算清单
        $order_data = [
            'order_no' => $param['order_no'] ?? '0',
            'user_id' => $param['user_id'] ?? $orderInfo['user_id'],
            'clean_no' => createNo(5),
            'business_type' => $param['business_type'],  // 编号
            'business_no'=> $param['business_no'],
            'order_type' => $param['order_type'] ?? 1,
            'auth_no'=> $param['out_auth_no'] ??  '',
            'payment_no'=> $param['out_payment_no'] ??  '',
            'auth_deduction_amount'=>    $param['auth_deduction_amount'] ?? 0.00 ,
            'auth_deduction_time'=>  $param['auth_deduction_time'] ??  0 ,
            'auth_deduction_status'=>    $authDeductionStatus ?? 0 ,
            'auth_unfreeze_amount'=>    $param['auth_unfreeze_amount']   ??  0.00 ,
            'auth_unfreeze_time'=>   $param['auth_unfreeze_time']   ??  0 ,
            'auth_unfreeze_status'=> $authUnfreezeStatus  ??  0 ,
            'refund_amount'=>   $param['refund_amount']  ??  0.00 ,
            'refund_time'=>     $param['refund_time']  ??  0 ,
            'refund_status'=>   $authRefundStatus  ??  0 ,
            'status'=>  $status  ??  0 ,
            'create_time'=>time(),
            'update_time'=>time(),
            'app_id' =>  $param['app_id'] ?? $orderInfo['appid'],
            'channel_id' =>  $param['channel_id'] ?? $orderInfo['channel_id'],
            'out_account'=> $param['pay_type'] ?? $orderInfo['pay_type'],
            'out_refund_no'=>   $param['out_refund_no'] ??  '',
            'out_unfreeze_trade_no'=> $param['out_unfreeze_trade_no'] ??  '',
            'out_unfreeze_pay_trade_no'=> $param['out_unfreeze_pay_trade_no'] ??  '',
            'auth_deduction_no'=>    $authDeductionNo ?? 0 ,
            'auth_unfreeze_no'=>    $authUnfreezeNo ?? 0 ,
            'refund_clean_no'=>    $refundCleanNo ?? 0 ,

        ];
        LogApi::debug("[clear]创建结算清单参数",$order_data);
        $success =$orderClearData->insert($order_data);
        if(!$success){
            return false;
        }
        return $order_data['clean_no'];
}

    /**
     * 获取订单清算详情数据
     * Author: heaven
     * @param $param
     * @return array|bool
     */
    public static function getOrderCleanInfo($param)
    {
        if (empty($param)) {
            return false;
        }
        $whereArray = array();
        if (isset($param['clean_no'])) {
            $whereArray[] = ['clean_no', '=', $param['clean_no']];
        }
        if (isset($param['order_no'])) {
            $whereArray[] = ['order_no', '=', $param['order_no']];
        }
        if (isset($param['auth_unfreeze_no'])) {
            $whereArray[] = ['auth_unfreeze_no', '=', $param['auth_unfreeze_no']];
        }
        if (isset($param['auth_deduction_no'])) {
            $whereArray[] = ['auth_deduction_no', '=', $param['auth_deduction_no']];
        }
        if (isset($param['refund_clean_no'])) {
            $whereArray[] = ['refund_clean_no', '=', $param['refund_clean_no']];
        }
        if (isset($param['order_type'])) {
            $whereArray[] = ['order_type', '=', $param['order_type']];
        }
        $orderData =  OrderClearing::where($whereArray)->first();
        if ($orderData) {
            return  $orderData->toArray();
        }
        return false;
        }





    /**
     * 退款结算数据列表
     * Author: heaven
     * @param $param
     * @param int $limit
     * @return mixed
     */
    public static function getOrderCleanList($param, $limit=2)
    {
        $whereArray = array();
        //出账状态
        //根据订单编号
        if (isset($param['order_no']) && !empty($param['order_no'])) {

            $whereArray[] = ['order_no', '=', $param['order_no']];
        }

        //应用来源ID
        if (isset($param['app_id']) && !empty($param['app_id'])) {
            $whereArray[] = ['channel_id', '=', $param['app_id']];
        }

        //出账类型
        if (isset($param['out_type']) && !empty($param['out_type'])) {
            $whereArray[] = ['business_type', '=', $param['out_type']];
        }

        //出账方式
        if (isset($param['out_account']) && !empty($param['out_account'])) {
            $whereArray[] = ['out_account', '=', $param['out_account']];
        }

        //出账状态
        if (isset($param['status']) && !empty($param['status'])) {
            $whereArray[] = ['status', '=', $param['status']];
        }

        //创建时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && empty($param['end_time'])) {
            $whereArray[] = ['create_time', '>=', strtotime($param['begin_time'])];
        }

        //创建时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['create_time', '>=', strtotime($param['begin_time'])];
            $whereArray[] = ['create_time', '<', (strtotime($param['end_time'])+3600*24)];
        }
        $query = OrderClearing::where($whereArray)->orderBy('create_time','DESC');

        if (isset($param['size']) && !empty($param['size'])) {

            $limit  =    $param['size'];
        }
        return $query->paginate($limit,
            ['*'], 'page', $param['page'])->toArray();
    }




    /**
     * 订单清算取消接口
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function cancelOrderClean($param)
    {
        if (empty($param)) {
            return false;
        }
        $whereArray[] = ['clean_no', '=', $param['clean_no']];
        $orderData =  OrderClearing::where($whereArray)->first();
        if (!$orderData) return false;
        $orderData->status  = OrderCleaningStatus::orderCleaningCancel;
        $orderData->update_time = time();
        $success =$orderData->save();
        if(!$success){
            return false;
        }
        return true;

    }



    /**
     * 更新小程序结算退款单数据
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function upMiniOrderCleanStatus($param){
        if (empty($param)) {
            return false;
        }
        $whereArray[] = ['order_no', '=', $param['order_no']];
        $whereArray[] = ['order_type', '=', OrderStatus::orderMiniService];
        $orderData =  OrderClearing::where($whereArray)->first();
        if (!$orderData) return false;
        if ($orderData->auth_unfreeze_status    ==  OrderCleaningStatus::depositUnfreezeStatusUnpayed) {
            $orderData->auth_unfreeze_status  = OrderCleaningStatus::depositUnfreezeStatusPayd;
            $orderData->auth_unfreeze_time  = time();
            //判断预授权转支付是否为待支付状态，如果是，变更为已支付
            if ($orderData->auth_deduction_status == OrderCleaningStatus::depositDeductionStatusUnpayed) {
                $orderData->auth_deduction_status = OrderCleaningStatus::depositDeductionStatusPayd;
                $orderData->auth_deduction_time = time();
                $orderData->out_unfreeze_pay_trade_no = $param['out_unfreeze_pay_trade_no'];
            }

        }
        $orderData->status  = OrderCleaningStatus::orderCleaningComplete;
        $orderData->update_time = time();
        $success =$orderData->save();
        if(!$success){
            return false;
        }
        return true;
    }




    /**
     * 更新乐百分结算完成状态
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function upLebaiOrderCleanStatus($param){
        if (empty($param)) {
            return false;
        }
        $whereArray[] = ['payment_no', '=', $param['payment_no']];
        $orderData =  OrderClearing::where($whereArray)->first();
        if (!$orderData) return false;
        if ($orderData->auth_unfreeze_status    ==  OrderCleaningStatus::depositUnfreezeStatusUnpayed) {
            $orderData->auth_unfreeze_status  = OrderCleaningStatus::depositUnfreezeStatusPayd;
            $orderData->auth_unfreeze_time  = time();
            //判断预授权转支付是否为待支付状态，如果是，变更为已支付
            if ($orderData->auth_deduction_status == OrderCleaningStatus::depositDeductionStatusUnpayed) {
                $orderData->auth_deduction_status = OrderCleaningStatus::depositDeductionStatusPayd;
                $orderData->auth_deduction_time = time();
            }

        }
        $orderData->status  = OrderCleaningStatus::orderCleaningComplete;
        $orderData->update_time = time();
        $success =$orderData->save();
        if(!$success){
            return false;
        }
        return true;
    }

    /**
     * 更新结算退款单数据
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function upOrderCleanStatus($param){
        if (empty($param)) {
            return false;
        }
        $whereArray[] = ['clean_no', '=', $param['clean_no']];
        $orderData =  OrderClearing::where($whereArray)->first();

        if (!$orderData) return false;


        //更新清算状态
        if (isset($param['status']) && !empty($param['status']) && in_array($param['status'],array_keys(OrderCleaningStatus::getOrderCleaningList()))) {


            $orderData->status  = $param['status'];

        }

        //更新退款状态
        if (isset($param['refund_status']) && !empty($param['refund_status']) && in_array($param['refund_status'],array_keys(OrderCleaningStatus::getRefundList()))) {

            $orderData->refund_status  = $param['refund_status'];
            if ($param['refund_status']==OrderCleaningStatus::refundPayd) {

                $orderData->refund_time  = time();
                $orderData->out_refund_no   = $param['out_refund_no'];

            }
        }

        //更新退款押金状态
        if (isset($param['auth_unfreeze_status']) && !empty($param['auth_unfreeze_status']) && in_array($param['auth_unfreeze_status'],array_keys(OrderCleaningStatus::getDepositUnfreezeStatusList()))) {

            $orderData->auth_unfreeze_status  = $param['auth_unfreeze_status'];
            if ($param['auth_unfreeze_status']==OrderCleaningStatus::depositUnfreezeStatusPayd) {

                $orderData->auth_unfreeze_time  = time();
                $orderData->out_unfreeze_trade_no   = $param['out_unfreeze_trade_no'];
            }
        }


        //更新扣除押金状态
        if (isset($param['auth_deduction_status']) && !empty($param['auth_deduction_status']) && in_array($param['auth_deduction_status'],array_keys(OrderCleaningStatus::getDepositDeductionStatusList()))) {

            $orderData->auth_deduction_status  = $param['auth_deduction_status'];
            if ($param['auth_deduction_status']==OrderCleaningStatus::depositDeductionStatusPayd) {

                $orderData->auth_deduction_time  = time();
                $orderData->out_unfreeze_pay_trade_no   = $param['out_unfreeze_pay_trade_no'];
            }
        }

        if (isset($param['operator_uid'])) {
            $orderData->operator_uid = $param['operator_uid'];
            $orderData->operator_username   =   $param['operator_username'] ?? '';
            $orderData->operator_type       =   $param['operator_type'] ??  0;
        }

        if (isset($param['mini_recover_transfer_num'])) {
            $orderData->mini_recover_transfer_num   =   $param['mini_recover_transfer_num'] ?? '0.00';
            $orderData->mini_recover_transfer_remark       =   $param['mini_recover_transfer_remark'] ??  '';
        }

        $orderData->update_time = time();

        $success =$orderData->save();

        if(!$success){
            return false;
        }
        return true;
    }
















}