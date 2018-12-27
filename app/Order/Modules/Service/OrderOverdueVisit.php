<?php
/**
 * 逾期回访
 */
namespace App\Order\Modules\Service;
use App\Order\Modules\Repository\OrderOverdueVisitRepository;
use App\Lib\Common\LogApi;


class OrderOverdueVisit
{
    /**
     * 获取逾期扣款信息
     * @author qinliping
     * @param array $order_no  订单编号
     * @return array
     */
    public static function getOverdueVisitInfo(string $order_no){
        if( !isset( $order_no ) ){
            return false;
        }
        $where[] = ['order_no','=',$order_no];
        $overDueDetail = OrderOverdueVisitRepository::getOverdueVisitinfo($where);
        return $overDueDetail;

    }

    /**
     * 创建回访记录
     * @param array $params
     * [
     *   'order_no'  =>'', //订单编号  【必选】   string
     *   'visit_id'  =>'', //回访id    【必选】   int
     *   'visit_text'=>''  //回访备注  【必选】   string
     * ]
     * @return true|false
     */
    public static function createVisit(array $params){

        $overDueDetail = OrderOverdueVisitRepository::createVisit($params);
        return $overDueDetail;
    }



}
