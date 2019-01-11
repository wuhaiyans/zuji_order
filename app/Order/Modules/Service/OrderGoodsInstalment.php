<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Lib\ApiStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class OrderGoodsInstalment
{

    /**
     * 查询分期数据
     * @params array 查询条件
     * @return array
     */
    public static function queryInfo($params){
        if (empty($params)) {
            return ApiStatus::CODE_20001;
        }

        $result =  OrderGoodsInstalmentRepository::getInfo($params);
        if(!$result){
            return ApiStatus::CODE_71001;
        }
        return $result;
    }

    /**
     * 根据InstalmentId查询分期数据
     * @return array
     */
    public static function queryByInstalmentId($id){
        if (empty($id)) {
            return ApiStatus::CODE_20001;
        }

        $result =  OrderGoodsInstalmentRepository::getInfoById($id);
        if(!$result){
            return ApiStatus::CODE_71001;
        }
        return $result;
    }


    /**
     * 根据business_no查询分期数据
     * @return array
     */
    public static function getByBusinessNo($business_no){
        if (empty($business_no)) {
            return ApiStatus::CODE_20001;
        }

        $result =  OrderGoodsInstalmentRepository::getInfo(['business_no'=>$business_no]);
        if(!$result){
            return ApiStatus::CODE_71001;
        }
        return $result;
    }

    /**
     * 查询分期数据
     * @return array
     */
    public static function queryList($params = [],$additional = []){
        if (!is_array($params)) {
            return ApiStatus::CODE_20001;
        }

        $params = filter_array($params, [
            'goods_no'  =>'required',
            'order_no'  =>'required',
            'status'    => 'required',
            'mobile'    => 'required',
            'term'      => 'required',
        ]);

        $additional = filter_array($additional, [
            'page'  =>'required',
            'limit'  =>'required',
        ]);

        $result =  OrderGoodsInstalmentRepository::queryList($params, $additional);
        $result = array_group_by($result,'goods_no');

        return $result;
    }

    /**
     * 是否允许扣款
     * @param  int  $instalment_id 订单分期付款id
     * @return bool true false
     */
    public static function allowWithhold($instalment_id){
        if(empty($instalment_id)){
            return false;
        }
        $alllow = false;
        $instalment_info = OrderGoodsInstalmentRepository::getInfoById($instalment_id);

        $status = $instalment_info['status'];

        $term 	= date("Ym");
        $day 	= intval(date("d"));

        if($status == OrderInstalmentStatus::UNPAID || $status == OrderInstalmentStatus::FAIL){
            // 本月15后以后 可扣当月 之前没有扣款的可扣款
            if(($term == $instalment_info['term'] && $day >= $instalment_info['day']) || $term > $instalment_info['term']){
                $alllow = true;
            }
        }
        return $alllow;
    }


    /**
     * 更新分期扣款的租机交易码
     * @param int $id	主键ID
     * @param string $business_no	交易码
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function set_trade_no($id, $business_no){
        if(!$id){
            return ApiStatus::CODE_20001;
        }

        if(!$business_no){
            return ApiStatus::CODE_20001;
        }

        return OrderGoodsInstalmentRepository::setTradeNo($id, $business_no);

    }

    /**
     * 更新分期扣款的租机交易码
     * @param int $id	主键ID
     * @param string $business_no	交易码
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function instalment_failed($fail_num,$instalment_id){

        $date = date('Ymd');

        $fail_num = intval($fail_num) + 1;
        $data = [
            'status'                => OrderInstalmentStatus::FAIL,
            'fail_num'              => $fail_num,
            'crontab_faile_date'    => $date,

        ];
        //修改失败次数
        $b = OrderGoodsInstalmentRepository::save(['id'=>$instalment_id],$data);
        if(!$b){
            Log::error('更新失败次数失败');
        }
        return $b;
    }


    /**
     * 修改方法
     * @param string $params 条件
     * @param string $data	 参数数组
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function save($params, $data){
        if (!is_array($params) || $data == [] ) {
            return false;
        }
        $result =  OrderGoodsInstalmentRepository::save($params, $data);
        return $result;
    }

    /**
     * 冻结分期
     * @param string $goods_no 商品单号
     * @return bool
     */
    public static function instalment_unfreeze($goods_no){
        if ( !$goods_no ) {
            return false;
        }
        $where = [
            'goods_no' => $goods_no,
        ];
        $result =  OrderGoodsInstalmentRepository::save($where, ['unfreeze_status'=>0,'status'=>OrderInstalmentStatus::CANCEL]);
        return $result;
    }


    /**
     * 关闭分期
     * @param string $params 条件
     * @param string $data	 参数数组
     * @return mixed  false：更新失败；int：受影响记录数
     */
    public static function close($params){
        if ( !is_array($params) || $params == []) {
            return false;
        }

        $data = [
            'status'    =>OrderInstalmentStatus::CANCEL,
        ];
        $result =  OrderGoodsInstalmentRepository::save($params, $data);
        return $result;
    }

    /**
     * 逾期分期
     * @return array [order_no]  逾期订单
     */
    public static function instalmentOverdue(){

        $order_status   = \App\Order\Modules\Inc\OrderStatus::OrderInService;  //在服务中
        $order_type     = \App\Order\Modules\Inc\OrderStatus::orderMiniService;  //去除小程序订单
        $zuqi_type      = \App\Order\Modules\Inc\OrderStatus::ZUQI_TYPE1;  //去除短租
        $status         = OrderInstalmentStatus::FAIL;  // 扣款失败
        $fileNum        = 2;  // 连续扣款失败次数

        $sql = "SELECT order_no,times,amount FROM
                `order_goods_instalment`
                WHERE order_no IN(
                    SELECT
                        order_no
                    FROM
                    (
                            SELECT
                                count(*) AS num,
                                order_goods_instalment.order_no
                            FROM
                                `order_goods_instalment`
                            LEFT JOIN `order_info` ON `order_info`.`order_no` = `order_goods_instalment`.`order_no`
                            LEFT JOIN `order_overdue_deduction` ON `order_overdue_deduction`.`order_no` = `order_goods_instalment`.`order_no`
                            WHERE
                                (
                                    `order_info`.`order_status` = " . $order_status . "
                                    AND `order_info`.`order_type` <> " . $order_type . "
                                    AND `order_info`.`zuqi_type` <> " . $zuqi_type . "
                                    AND `order_goods_instalment`.`status` = " . $status . "
//                                    AND `order_overdue_deduction`.`order_no` IS NULL
                                )
                            GROUP BY
                                `order_goods_instalment`.`order_no`
                            HAVING
                                `num` >= ".$fileNum."
                            ORDER BY
                                `order_info`.`id` DESC
                        ) as tmp GROUP BY order_no
                ) AND status = " . $status;

        $result = DB::select($sql);
        $instalmentList = objectToArray($result);

        // 以订单为维度 分组
        $instalmentList = array_group_by($instalmentList, 'order_no');

//
//        $whereArray = [
//            ['order_info.order_status', '=', \App\Order\Modules\Inc\OrderStatus::OrderInService],   //在服务中
//            ['order_info.order_type', '<>', \App\Order\Modules\Inc\OrderStatus::orderMiniService],  //去除小程序订单
//            ['order_goods_instalment.status', '=', OrderInstalmentStatus::FAIL],   // 扣款失败
//        ];
//
//        /**
//         * 订单在服务中 分期连续两个月 未扣款成功
//         */
//        $result =  \App\Order\Models\OrderGoodsInstalment::select(
//            DB::raw("count(*) as num,order_goods_instalment.order_no"))
//            ->where($whereArray)
//            ->whereNull('order_overdue_deduction.order_no')
//            ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
//            ->leftJoin('order_overdue_deduction', 'order_overdue_deduction.order_no', '=', 'order_goods_instalment.order_no')
//            ->groupBy('order_goods_instalment.order_no')
//            ->orderBy('order_info.id','DESC')
//            ->having('num', '>=', 2)
//            ->get();
//        if (!$result) return false;
//        $instalmentList =  $result->toArray();
//        if(!$instalmentList){
//            return [];
//        }

        $array = [];
        // 循环查询
        foreach($instalmentList as $key => &$item){
            // 如果总数小于两期 且不是连续的两期 则去除 此条数据
            if(count($item) < 3){
                if(abs($item[0]['times'] - $item[1]['times']) > 1){
                    continue;
                    //array_splice($instalmentList,$key,1);
                }
            }
            $amount = 0;
            foreach($item as $v){
                $amount +=$v['amount'];
            }

            $orderInfo['order_no']  = $key;
            $orderInfo['amount']    = $amount;

            $array[] = $orderInfo;
        }

        return $array;
    }



}