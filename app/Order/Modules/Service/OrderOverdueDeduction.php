<?php
/**
 * 逾期扣款
 */
namespace App\Order\Modules\Service;

use App\Lib\Order\OrderInfo;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\OrderStatus;
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
        $overdueInfoArray = objectToArray($overdueInfo);
        if (!empty($overdueInfoArray['data'])) {
            foreach ($overdueInfoArray['data'] as $keys=>$values) {

                //应用来源
                $overdueInfoArray['data'][$keys]['order_source_name'] = OrderInfo::getAppidInfo($values['app_id']);

                //回访标识
                $overdueInfoArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? OrderStatus::getVisitName($values['visit_id']):OrderStatus::getVisitName(OrderStatus::visitUnContact);

                //租期类型
                $overdueInfoArray['data'][$keys]['zuqi_name'] =  OrderStatus::getZuqiTypeName($values['zuqi_type']);

                //扣款状态
                $overdueInfoArray['data'][$keys]['deduction_name'] = OrderInstalmentStatus::getStatusName($values['deduction_status']);

            }

        }

        return $overdueInfoArray;
    }

}
