<?php
/**
 *
 * 订单结算数据处理
 */
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Order\Models\OrderClearing;

class OrderClearingRepository
{


    /**
     * 退款结算数据录入
     * @param $param
     * @return bool|string
     */
    public function createOrderClean($param){

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
        ];
        $success =$orderClearData->create($order_data);
        if(!$success){
            return false;
        }
        return true;
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
    public static function getOrderCleanList($param, $limit, $page=null)
    {

        $whereArray = array();
        //根据用户id
        if (isset($param['user_id']) && !empty($param['user_id'])) {

            $whereArray[] = ['user_id', '=', $param['user_id']];
        }
        $query = OrderClearing::where($whereArray);


        return $query->paginate($limit,
            [
                'receive_no','order_no', 'logistics_id','logistics_no',
                'status', 'create_time', 'receive_time','check_description',
                'status_time','check_time','check_result'
            ],
            'page', $page);
    }

    /**
     *
     * 更新结算退款单数据
     *
     */

    public static function upOrderCleanById($orderNo){
        if (empty($param)) {
            return false;
        }
        $whereArray = array();
        foreach($param as $keys=>$values) {

            $whereArray[] = [$keys, '=', $values];
        }

        $orderData =  OrderClearing::where($whereArray)->first();
        if (!$orderData) return false;

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
        ];

        $success =$orderData->save($order_data);
        if(!$success){
            return false;
        }
        return true;
    }
















}