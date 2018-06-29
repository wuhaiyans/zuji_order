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
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Models\OrderClearing;
use App\Order\Modules\Inc\OrderStatus;
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
        if (empty($param) || empty($param['order_no'])) {
            return false;
        }

        $orderClearData = new OrderClearing();
        //根据订单号查询订单信息
        $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$param['order_no']));
        if (isset($param['auth_deduction_amount'])  && $param['auth_deduction_amount']>0) $authDeductionStatus = OrderCleaningStatus::depositDeductionStatusUnpayed;
        if (isset($param['auth_unfreeze_amount'])  &&  $param['auth_unfreeze_amount']>0) $authUnfreezeStatus = OrderCleaningStatus::depositDeductionStatusUnpayed;
        if (isset($param['refund_amount'])  &&  $param['refund_amount']>0) $authRefundStatus = OrderCleaningStatus::depositDeductionStatusUnpayed;

        //预授权转支付，预授权解押，退款金额全为空，清算状态设为已完成
        if (empty($param['auth_deduction_amount']) && empty($param['auth_unfreeze_amount']) && empty($param['refund_amount']))
        {
            $status    =   OrderCleaningStatus::orderCleaningComplete;
            //预授权转支付不为空，为待押金转支付状态
        } else if (!empty($param['auth_deduction_amount'])){

            $status    =   OrderCleaningStatus::orderCleaningDeposit;

            //预授权转支付不为空，为待押金转支付状态
        }   else if (empty($param['auth_deduction_amount']) && !empty($param['auth_unfreeze_amount'])){

            $status    =   OrderCleaningStatus::orderCleaningUnfreeze;

        }   else if (empty($param['auth_deduction_amount']) && empty($param['auth_unfreeze_amount']) && !empty($param['refund_amount'])){

            $status    =   OrderCleaningStatus::orderCleaningUnRefund;
        }

        if (empty($orderInfo)) return false;
        // 创建结算清单
        $order_data = [
            'order_no' => $param['order_no'],
            'user_id' => $orderInfo['user_id'],
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
            'app_id' => $orderInfo['appid'],
            'out_account'=> $orderInfo['pay_type'],
            'out_refund_no'=>   $param['out_refund_no'] ??  '',
            'out_unfreeze_trade_no'=> $param['out_unfreeze_trade_no'] ??  '',
            'out_unfreeze_pay_trade_no'=> $param['out_unfreeze_pay_trade_no'] ??  '',

        ];
      // sql_profiler();
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
            $whereArray[] = ['app_id', '=', $param['app_id']];
        }

        //出账类型
        if (isset($param['out_type']) && !empty($param['out_type'])) {
            $whereArray[] = ['refund_amount', '>', 0];
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
            $whereArray[] = ['create_time', '>=', $param['begin_time']];
        }

        //创建时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['create_time', '>=', $param['begin_time']];
            $whereArray[] = ['create_time', '<=', $param['end_time']];
        }
        $query = OrderClearing::where($whereArray);

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

        $orderData->update_time = time();
        $success =$orderData->save();
        if(!$success){
            return false;
        }
        return true;
    }
















}