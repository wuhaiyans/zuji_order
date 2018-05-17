<?php
/**
 *
 * 订单结算数据处理
 */
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Models\OrderClearing;

class OrderClearingRepository
{


    /**
     * 退款结算数据录入
     * @param $param
     * @return bool|string
     */
    public static function createOrderClean($param){

        if (empty($param)) {
            return false;
        }
        $orderClearData = new OrderClearing();
        // 创建结算清单
        $order_data = [
            'order_no' => $param['order_no'],
            'business_type' => $param['business_type'],  // 编号
            'business_no'=> $param['business_no'],
            'claim_name'=>  isset($param['claim_name'])?$param['claim_name']:0 ,
            'claim_amount'=>    isset($param['claim_amount'])?$param['claim_amount']:0.00 ,
            'claim_time'=>  isset($param['claim_time'])?$param['claim_time']:0 ,
            'claim_status'=>    isset($param['claim_status'])?$param['claim_status']:0 ,
            'deposit_deduction_amount'=>    isset($param['deposit_deduction_amount'])?$param['deposit_deduction_amount']:0.00 ,
            'deposit_deduction_time'=>  isset($param['deposit_deduction_time'])?$param['deposit_deduction_time']:0 ,
            'deposit_deduction_status'=>    isset($param['deposit_deduction_status'])?$param['deposit_deduction_status']:0 ,
            'deposit_unfreeze_amount'=>     isset($param['deposit_unfreeze_amount'])?$param['deposit_unfreeze_amount']:0.00 ,
            'deposit_unfreeze_time'=>   isset($param['deposit_unfreeze_time'])?$param['deposit_unfreeze_time']:0 ,
            'deposit_unfreeze_status'=>  isset($param['deposit_unfreeze_status'])?$param['deposit_unfreeze_status']:0 ,
            'refund_amount'=>   isset($param['refund_amount'])?$param['refund_amount']:0.00 ,
            'refund_time'=>     isset($param['refund_time'])?$param['refund_time']:0 ,
            'refund_status'=>   isset($param['refund_status'])?$param['refund_status']:0 ,
            'status'=>  isset($param['status'])?$param['status']:0 ,
            'create_time'=>time(),
            'update_time'=>time(),
            'app_id' => 1,
            'out_account'=>2,
        ];
        $success =$orderClearData->insert($order_data);
        if(!$success){
            return false;
        }
        return true;
}

    /**
     *
     *  获取订单清算详情数据
     *
     */
    public static function getOrderCleanInfo($param)
    {

        if (empty($param)) {
            return false;
        }
        $whereArray = array();
        if (isset($param['business_type']) &&  isset($param['business_no'])){
            $whereArray[] = ['business_type', '=', $param['business_type']];
            $whereArray[] = ['business_no', '=', $param['business_no']];
        }

        $orderData =  OrderClearing::where($whereArray)->first()->toArray();
        return $orderData;

    }


    /**
     *
     *  退款结算数据列表
     */
    /**
     * @param $params
     * @param $limit
     * @param null $page
     * @return array

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

        return $query->paginate($limit,
            ['*'], 'page', $param['page'])->toArray();
    }


    /**
     *
     * 订单清算取消接口
     * @param $param
     *
     *
     */
    public static function cancelOrderClean($param)
    {
        if (empty($param)) {
            return false;
        }
        $whereArray[] = ['business_type', '=', $param['business_type']];
        $whereArray[] = ['business_no', '=', $param['business_no']];
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
     *
     * 更新结算退款单数据
     *
     */

    public static function upOrderCleanStatus($param){
        if (empty($param)) {
            return false;
        }
        $whereArray[] = ['business_type', '=', $param['business_type']];
        $whereArray[] = ['business_no', '=', $param['business_no']];
        $orderData =  OrderClearing::where($whereArray)->first();
        if (!$orderData) return false;
        $orderData->status  = $param['status'];
        $orderData->update_time = time();
        $success =$orderData->save();
        if(!$success){
            return false;
        }
        return true;
    }
















}