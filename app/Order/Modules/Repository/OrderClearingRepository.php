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
        if (empty($orderInfo)) return false;
        // 创建结算清单
        $order_data = [
            'order_no' => $param['order_no'],
            'user_id' => $param['user_id'],
            'out_refund_no' => createNo(5),
            'business_type' => $param['business_type'],  // 编号
            'business_no'=> $param['business_no'],
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
            'app_id' => $orderInfo['appid'],
            'out_account'=>$orderInfo['pay_type'],
        ];
        $success =$orderClearData->insert($order_data);
        if(!$success){
            return false;
        }
        return true;
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
        if (isset($param['out_refund_no'])){
            $whereArray[] = ['out_refund_no', '=', $param['out_refund_no']];
            $orderData =  OrderClearing::where($whereArray)->first()->toArray();
            return $orderData;
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
     * 更新结算退款单数据
     * Author: heaven
     * @param $param
     * @return bool
     */
    public static function upOrderCleanStatus($param){
        if (empty($param)) {
            return false;
        }
        $whereArray[] = ['out_refund_no', '=', $param['out_refund_no']];
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
                $orderData->refund_no   = $param['refund_no'];

            }
        }

        //更新退款押金状态
        if (isset($param['deposit_unfreeze_status']) && !empty($param['deposit_unfreeze_status']) && in_array($param['deposit_unfreeze_status'],array_keys(OrderCleaningStatus::getDepositUnfreezeStatusList()))) {

            $orderData->deposit_unfreeze_status  = $param['deposit_unfreeze_status'];
            if ($param['deposit_unfreeze_status']==OrderCleaningStatus::depositUnfreezeStatusPayd) {

                $orderData->deposit_unfreeze_time  = time();
                $orderData->out_auth_no   = $param['out_auth_no'];
            }
        }


        //更新扣除押金状态
        if (isset($param['deposit_deduction_status']) && !empty($param['deposit_deduction_status']) && in_array($param['deposit_deduction_status'],array_keys(OrderCleaningStatus::getDepositDeductionStatusList()))) {

            $orderData->deposit_deduction_status  = $param['deposit_deduction_status'];
            if ($param['deposit_deduction_time']==OrderCleaningStatus::depositDeductionStatusPayd) {

                $orderData->deposit_deduction_time  = time();
                $orderData->out_trade_no   = $param['out_trade_no'];
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