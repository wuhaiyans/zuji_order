<?php
/**
 * 逾期扣款
 */
namespace App\Order\Modules\Service;

use App\Lib\Order\OrderInfo;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\OrderOverdueStatus;
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
                $overdueInfoArray['data'][$keys]['appid_name'] = OrderInfo::getAppidInfo($values['app_id']);

                //回访标识
                $overdueInfoArray['data'][$keys]['visit_name'] = !empty($values['visit_id'])? OrderStatus::getVisitName($values['visit_id']):OrderStatus::getVisitName(OrderStatus::visitUnContact);

                //租期类型
                $overdueInfoArray['data'][$keys]['zuqi_name'] =  OrderStatus::getZuqiTypeName($values['zuqi_type']);

                //扣款状态
                $overdueInfoArray['data'][$keys]['deduction_name'] = OrderOverdueStatus::getStatusName($values['deduction_status']);

            }

        }

        return $overdueInfoArray;
    }

    /**
     * 逾期扣款列表导出
     * @param array $params
     * @param int $pagesize
     * @return mixed
     */
    public static function OverdueDeductionExport($params = array(),$pagesize = 5){
        $overdueInfo = OrderOverdueDeductionRepository::overdueDeductionListExport( $params,$pagesize );//获取逾期扣款列表
        if (!empty($overdueInfo)) {
            foreach ($overdueInfo as $keys=>$values) {

                //应用来源
                $overdueInfo[$keys]['appid_name'] = OrderInfo::getAppidInfo($values['app_id']);

                //回访标识
                $overdueInfo[$keys]['visit_name'] = !empty($values['visit_id'])? OrderStatus::getVisitName($values['visit_id']):OrderStatus::getVisitName(OrderStatus::visitUnContact);

                //租期类型
                $overdueInfo[$keys]['zuqi_name'] =  OrderStatus::getZuqiTypeName($values['zuqi_type']);

                //扣款状态
                $overdueInfo[$keys]['deduction_name'] = OrderOverdueStatus::getStatusName($values['deduction_status']);

            }

        }

        return $overdueInfo;
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
            $overdueDeductionInfo = OrderOverdueDeductionRepository::info(['business_no' => $businessNo]);
            if( !is_array($overdueDeductionInfo)){
                LogApi::error('[deduDepositNotify]逾期扣除押金数据错误');
                return false;
            }

            /**
             * 修改逾期表状态
             */

            $totalAmount = $overdueDeductionInfo['deduction_amount'] + $overdueDeductionInfo['total_amount'];
            $data = [
                'overdue_amount'    => $overdueDeductionInfo['overdue_amount'] - $overdueDeductionInfo['deduction_amount'], // 剩余押金金额
                'deduction_time'    => time(),
                'update_time'   	=> time(),
                'deduction_status'  => \App\Order\Modules\Inc\OrderOverdueStatus::SUCCESS,
                'total_amount'      => $totalAmount,
            ];
            $b = OrderOverdueDeductionRepository::save(['business_no' => $businessNo], $data);
            if(!$b){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]修改分期状态失败');
                return false;
            }

            /**
             * 修改逾期扣款记录表
             */

            $rData = [
                'status'  => \App\Order\Modules\Inc\OrderOverdueStatus::SUCCESS,
            ];
            $rb = \App\Order\Modules\Repository\OrderOverdueRecordRepository::save(['overdue_id' => $overdueDeductionInfo['id']],$rData);
            if(!$rb){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]修改分期扣款记录失败');
                return false;
            }


            /**
             * 修改商品表剩余押金
             */
            $goodsInfo = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsRow(['order_no'=>$overdueDeductionInfo['order_no']]);
            if(!$goodsInfo){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]查询商品信息错误');
                return false;
            }

            if($goodsInfo['surplus_yajin'] < $overdueDeductionInfo['deduction_amount']){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]剩余押金小于所扣除押金');
                return false;
            }

            $goodsData = [
                'surplus_yajin'             => $goodsInfo['surplus_yajin'] - $overdueDeductionInfo['deduction_amount'],// 剩余押金
            ];
            $orderGoods = new \App\Order\Modules\Repository\OrderGoodsRepository();
            $result = $orderGoods->update(['order_no' => $overdueDeductionInfo['order_no']], $goodsData);
            if(!$result){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]修改商品剩余押金失败');
                return false;
            }
        }

        return true;
    }

}
