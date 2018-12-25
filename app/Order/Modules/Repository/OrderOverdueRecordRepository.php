<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderOverdueRecord;

class OrderOverdueRecordRepository
{

    /** 获取逾期扣款记录列表
     * @$param overdue_id 逾期列表ID
     * @$List array
     */
    public static function getOverdueDeductionList($param = array())
    {
        $List = OrderOverdueRecord::query()
            ->where($param)
            ->get()
            ->toArray();
        if( !$List ){
            return [];

        }
        return $List;
    }

    /**
     * 创建记录
     * @param $data
     * [
     *      'overdue_id'        => 'required', //逾期表ID
     *      'deduction_amount'  => 'required', //扣款金额
     *      'overdue_amount'    => 'required', //剩余金额
     *      'remark'            => 'required', //扣款备注
     *      'status'            => 'required', //扣除押金状态0：无效；1：未支付；2：扣款成功；3：扣款失败；4：取消；5：扣款中''
     *      'create_time'       => 'required', //扣款时间
     * ]
     * @return bool|mixed
     */

    public static function create($data){

        $ret = OrderOverdueRecord::create($data);
        if(!$ret){
            return false;
        }
        return $ret->getQueueableId();

    }

    /**
     * 修改方法
     * array    $where
     * array    $data
     * return bool
     */
    public static function save($where, $data){
        if ( empty($where )) {
            return false;
        }
        if ( empty($data )) {
            return false;
        }

        $result =  OrderOverdueRecord::where($where)->update($data);
        if (!$result) return false;

        return true;
    }
}