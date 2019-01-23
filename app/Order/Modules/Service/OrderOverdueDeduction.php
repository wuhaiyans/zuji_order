<?php
/**
 * 逾期扣款
 */
namespace App\Order\Modules\Service;

use App\Lib\Order\OrderInfo;
use App\Order\Models\OrderOverdueRecord;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Inc\OrderOverdueStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderOverdueDeductionRepository;
use App\Order\Modules\Repository\OrderOverdueRecordRepository;
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

                if($values['order_status'] == OrderStatus::OrderClosedRefunded || $values['order_status'] == OrderStatus::OrderCompleted){
                    if($overdueInfoArray['data'][$keys]['overdue_amount'] != 0 || $overdueInfoArray['data'][$keys]['unpaid_amount'] != 0){
                        $where = [];
                        $where[] = ['order_no','=',(string)$values['order_no']];
                        $data = [
                            'overdue_amount'=> 0,
                            'unpaid_amount'=>  0
                        ];
                        //修改未缴租金和押金为0
                       $upResult =  \App\Order\Models\OrderOverdueDeduction::where($where)->update($data);
                       if( !$upResult ){
                           return false;
                       }
                    }

                    $overdueInfoArray['data'][$keys]['overdue_amount'] = 0;
                    $overdueInfoArray['data'][$keys]['unpaid_amount'] = 0;
                }else {
                    if($overdueInfoArray['data'][$keys]['status'] == OrderOverdueStatus::INVALID ){
                        //获取订单分期中的扣款失败金额
                        $whereArray = [];
                        $whereArray[] = ['order_no','=',(string)$values['order_no']];
                        $whereArray[] = ['status','=', OrderInstalmentStatus::FAIL ]; // 扣款失败
                        $sum_amount = OrderGoodsInstalmentRepository::getFallInstalment($whereArray);//获取扣款失败的总金额
                        if( !$sum_amount ){
                            return false;
                        }
                        if( $overdueInfoArray['data'][$keys]['unpaid_amount'] != $sum_amount){
                            $upWhere = [];
                            $upWhere[] = ['order_no','=',$values['order_no']];
                            $upData = [
                                'unpaid_amount'=>$sum_amount
                            ];
                            //修改未缴租金
                            $upUnpaidResult = \App\Order\Models\OrderOverdueDeduction::where($upWhere)->update($upData);
                            if( !$upUnpaidResult ){
                                return false;
                            }
                        }

                    }
                }

                 //应用来源
                $overdueInfoArray['data'][$keys]['appid_name'] = OrderInfo::getAppidInfo($values['app_id']);

                 //回访标识
                $overdueInfoArray['data'][$keys]['visit_name'] = !empty($values['v_id'])? OrderStatus::getVisitName($values['v_id']):OrderStatus::getVisitName(OrderStatus::visitUnContact);

                 //租期类型
                $overdueInfoArray['data'][$keys]['zuqi_name'] =  OrderStatus::getZuqiTypeName($values['zuqi_type']);

                 //扣款状态
                $overdueInfoArray['data'][$keys]['deduction_name'] = OrderOverdueStatus::getStatusName($values['deduction_status']);
                 //状态
                $overdueInfoArray['data'][$keys]['status_name'] = OrderOverdueStatus::getOverdueStatusName($values['status']);
                 //默认显示扣款按钮
                $overdueInfoArray['data'][$keys]['operate_status'] = true;
                 if( $overdueInfoArray['data'][$keys]['status'] == OrderOverdueStatus::INVALID ||  $overdueInfoArray['data'][$keys]['overdue_amount'] == 0){
                     $overdueInfoArray['data'][$keys]['operate_status'] = false;
                 }
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
        $overdue_info = [];
        if (!empty($overdueInfo)) {
            foreach ($overdueInfo as $keys=>$values) {
                $where =array();
                $where[] = ['overdue_id','=',$overdueInfo[$keys]['id']];
                $overdueRecord = OrderOverdueRecordRepository::getOverdueDeductionList($where);

                if( $overdueInfo[$keys]['status'] == OrderOverdueStatus::EFFECTIVE || $overdueRecord){
                    $overdue_info[$keys] = $overdueInfo[$keys];
                    //应用来源
                    $overdue_info[$keys]['appid_name'] = OrderInfo::getAppidInfo($values['app_id']);

                    //回访标识
                    $overdue_info[$keys]['visit_name'] = !empty($values['v_id']) ? OrderStatus::getVisitName($values['v_id']) : OrderStatus::getVisitName(OrderStatus::visitUnContact);
                    if ($values['d_status']) {
                        //扣款状态
                        $overdue_info[$keys]['deduction_name'] = OrderOverdueStatus::getStatusName($values['d_status']);
                    } else {
                        //扣款状态
                        $overdue_info[$keys]['deduction_name'] = OrderOverdueStatus::getStatusName(OrderOverdueStatus::UNPAID);
                    }
                    //扣款金额
                    if (empty($values['d_amount'])) {
                        $overdue_info[$keys]['d_amount'] = 0;
                    }
                    //扣款时间
                    if (empty($values['d_time'])) {
                        $overdue_info[$keys]['d_time'] = 0;
                    } else {
                        $overdue_info[$keys]['d_time'] = date('Y-m-d H:i:s', $values['d_time']);
                    }
                }

            }

        }

        return $overdue_info;
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
                'surplus_yajin'  => bcsub($goodsInfo['surplus_yajin'], $overdueDeductionInfo['deduction_amount'], 2),// 剩余押金
            ];
            $orderGoods = new \App\Order\Modules\Repository\OrderGoodsRepository();
            $result = $orderGoods->update(['order_no' => $overdueDeductionInfo['order_no']], $goodsData);
            if(!$result){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]修改商品剩余押金失败');
                return false;
            }

            /**
             * 增加入账记录
             */

            // 查询订单
            $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($overdueDeductionInfo['order_no']);
            if( !$orderInfo ){
                \App\Lib\Common\LogApi::error('[deduDepositNotify]逾期扣款回调-订单信息错误');
                return false;
            }

            $incomeData = [
                'name'           => $overdueDeductionInfo['order_no'] . "逾期扣除押金",
                'order_no'       => $overdueDeductionInfo['order_no'],
                'business_type'  => \App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,
                'business_no'    => $param['out_trade_no'],
                'appid'          => $orderInfo['appid'],
                'channel'        => \App\Order\Modules\Repository\Pay\Channel::Alipay,
                'amount'         => $overdueDeductionInfo['deduction_amount'],
                'create_time'    => time(),
                'trade_no'       => $param['out_trade_no'],
                'out_trade_no'   => isset($param['trade_no'])?$param['trade_no']:'',
            ];
            $incomeB = \App\Order\Modules\Repository\OrderPayIncomeRepository::create($incomeData);
            if(!$incomeB){
                \App\Lib\Common\LogApi::error('[OverdueDeductionNotify]创建收支明细失败');
                return false;
            }

            /**
             * 发送短信
             */
            $notice = new \App\Order\Modules\Service\OrderNotice(
                \App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,
                $param['out_trade_no'],
                "OverdueDeduction");
            $notice->notify();


        }

        return true;
    }

}
