<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderRiskCheckLog;

class OrderRiskCheckLogRepository
{

    protected $checkLog;


    public function __construct(OrderRiskCheckLog $checkLog)
    {
        $this->checkLog = $checkLog;
    }

    /**
     * 记录单日志
     * @param $operator_id 操作员ID
     * @param $operator_name 操作员名字
     * @param $operator_type 操作员类型
     * @param $orderNo
     * @param $newStatus 新状态
     * @param $oldStatus 原状态
     * @param $msg
     * @return mixed
     */
    public static function add($operatorId,$operatorName,$operatorType,$orderNo, $msg,$newStatus,$oldStatus=0 ){
        $data =[
            'operator_id'=>$operatorId,
            'operator_name'=>$operatorName,
            'operator_type'=>$operatorType,
            'order_no'=>$orderNo,
            'new_status'=>$newStatus,
            'old_status'=>$oldStatus,
            'msg'=>$msg,
            'system_time'=>time(),
        ];
        $log =OrderRiskCheckLog::create($data);
        return $log->getQueueableId();
    }

    /**
     * 获取订单风控审核日志
     * @param $orderNo 订单号
     * @return array|bool
     */
    public static function getOrderLog($orderNo)
    {
        if (empty($orderNo)) return false;
        $LogData = OrderRiskCheckLog::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        return $LogData ?? false;
    }


}