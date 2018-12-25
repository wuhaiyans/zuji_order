<?php
/**
 * 逾期扣款
 */
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderOverdueDeductionRepository;

class OrderOverdueDeduction
{
    /**
     * 获取逾期扣款信息
     * @author qinliping
     * @param array $params
     * @return array
     */
    public static function getOverdueDeductionInfo($params = array()){
        $overdueInfo = OrderOverdueDeductionRepository::getOverdueDeductionList( $params );//获取逾期扣款列表
        print_r($overdueInfo);

    }

}
