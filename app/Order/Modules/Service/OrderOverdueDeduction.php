<?php
/**
 * 逾期扣款
 */
namespace App\Order\Modules\Service;

use App\Lib\Order\OrderInfo;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderOverdueDeductionRepository;
use Illuminate\Support\Facades\DB;
use App\Lib\Common\LogApi;


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

    /**
     * 逾期扣除押金回调处理
     * @author maxiaoyu
     * @param array $params
     * @return array
     */
    public static function OverdueDeductionNotify(array $param){
        if($param['status'] == "success"){

            $businessNo = $param['out_trade_no'];

            LogApi::info("[deduDepositNotify]逾期扣除押金回调", $param);
            $OverdueDeductionInfo = self::getOverdueDeductionInfo(['business_no' => $businessNo]);
            if( !is_array($OverdueDeductionInfo)){
                LogApi::error('[deduDepositNotify]逾期扣除押金数据错误');
                return false;
            }

            /**
             * 修改逾期表状态
             */
            $data = [
                'overdue_amount'    => $OverdueDeductionInfo['overdue_amount'] - $OverdueDeductionInfo['deduction_amount'], // 剩余押金金额
                'deduction_time'    => time(),
                'update_time'   	=> time(),
                'deduction_status'  => 2,

            ];
            $b = OrderOverdueDeductionRepository::save(['business_no' => $businessNo], $data);
            if(!$b){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]修改分期状态失败');
                return false;
            }


            /**
             * 修改商品表剩余押金
             */
            $goodsData = [
                'surplus_yajin'             => 'surplus_yajin' - $OverdueDeductionInfo['deduction_amount'],// 剩余押金
            ];
            $orderGoods = new \App\Order\Modules\Repository\OrderGoodsRepository();
            $result = $orderGoods->update(['order_no' => $OverdueDeductionInfo['order_no']], $goodsData);
            if(!$result){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]修改商品剩余押金失败');
                return false;
            }
        }

        return true;
    }

}
