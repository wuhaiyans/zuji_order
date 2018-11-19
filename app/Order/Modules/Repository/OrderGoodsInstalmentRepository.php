<?php
namespace App\Order\Modules\Repository;

use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use App\Order\Models\OrderGoodsInstalment;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Profiler;

class OrderGoodsInstalmentRepository
{

    /**
     * 根据id查询信息
     */
    public static function getInfoById($id){
        if (empty($id)) return false;
        $result =  OrderGoodsInstalment::query()->where([
            ['id', '=', $id],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }

    /**
     * 查询分期信息
     */
    public static function getInfo($params){
        if (empty($params)) return false;
        $result =  OrderGoodsInstalment::query()->where($params)->first();
        if (!$result) return false;
        return $result->toArray();
    }
    /**
     * 查询分期统计应付金额
     */
    public static function getSumAmount($params){
        if (empty($params)) return false;
        $result =  OrderGoodsInstalment::select(DB::raw("count(*) as fenqishu,sum(amount) as amount"))->where($params)->first();
        if (!$result) return false;
        return $result;
    }
    /**
     * 查询总数
     */
    public static function queryCount($param = []){
        $whereArray = [];
        $statusArr = [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::SUCCESS, OrderInstalmentStatus::FAIL, OrderInstalmentStatus::CANCEL, OrderInstalmentStatus::PAYING];

        // 开始时间（可选）
        if( isset($param['begin_time']) && $param['begin_time'] != ""){
            $whereArray[] =  ['term', '>=', $param['begin_time']];
        }

        // 开始时间（可选）
        if( isset($param['end_time']) && $param['end_time'] != ""){
            $whereArray[] =  ['term', '<=', $param['end_time']];
        }

        //根据goods_no
        if (isset($param['goods_no']) && !empty($param['goods_no'])) {
            $whereArray[] = ['order_goods_instalment.goods_no', '=', $param['goods_no']];
        }

        //根据订单号
        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_goods_instalment.order_no', '=', $param['order_no']];
        }

        //根据分期状态
        if (isset($param['status']) && !empty($param['status'])) {
            if( is_array($param['status']) ){
                $statusArr = $param['status'];
            }else{
                $statusArr = [$param['status']];
            }
        }

        // 根据还款类型
        if (isset($param['pay_type']) && !empty($param['pay_type'])) {
            $whereArray[] = ['order_goods_instalment.pay_type', '=', $param['pay_type']];
        }

        //根据分期日期
        if (isset($param['term']) && !empty($param['term'])) {
            $whereArray[] = ['order_goods_instalment.term', '=', $param['term']];
        }

        //根据分期期数
        if (isset($param['times']) && !empty($param['times'])) {
            $whereArray[] = ['order_goods_instalment.times', '=', $param['times']];
        }

        //根据用户手机号
        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_info.mobile', '=', $param['mobile']];
        }

        if(isset($param['is_instalment_list'])){
            $whereArray[] = ['order_info.order_status', '=', \App\Order\Modules\Inc\OrderStatus::OrderInService];
        }

        $result = OrderGoodsInstalment::query()
            ->where($whereArray)
            ->whereIn('order_goods_instalment.status',$statusArr)
            ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
            ->count();
        return $result;//count($result);
    }
    /**
     * 查询列表
     */
    public static function queryList($param = [], $additional = []){
        $page       = isset($additional['page']) ? $additional['page'] : 1;
        $pageSize   = isset($additional['limit']) ? $additional['limit'] : config("web.pre_page_size");
        $offset     = ($page - 1) * $pageSize;

        $whereArray = [];
        $statusArr = [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::SUCCESS, OrderInstalmentStatus::FAIL, OrderInstalmentStatus::CANCEL, OrderInstalmentStatus::PAYING];

        // 开始时间（可选）
        if( isset($param['begin_time']) && $param['begin_time'] != ""){
            $whereArray[] =  ['term', '>=', $param['begin_time']];
        }

        // 开始时间（可选）
        if( isset($param['end_time']) && $param['end_time'] != ""){
            $whereArray[] =  ['term', '<=', $param['end_time']];
        }

        //根据goods_no
        if (isset($param['goods_no']) && !empty($param['goods_no'])) {
            $whereArray[] = ['order_goods_instalment.goods_no', '=', $param['goods_no']];
        }

        //逾期天数
        if(isset($param['beoverdue_day']) && !empty($param['beoverdue_day'])){
            $whereArray = getBeoverduetime($param['beoverdue_day']);
        }
        //根据订单号
        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_goods_instalment.order_no', '=', $param['order_no']];
        }

        /**
         * 根据分期状态 string 或 array 2018/09/15
         */
        if (isset($param['status']) && !empty($param['status'])) {
            if( is_array($param['status']) ){
                $statusArr = $param['status'];
            }else{
                $statusArr = [$param['status']];
            }
        }
        

        //根据分期日期
        if (isset($param['term']) && !empty($param['term'])) {
            $whereArray[] = ['order_goods_instalment.term', '=', $param['term']];
        }
        //根据分期期数
        if (isset($param['times']) && !empty($param['times'])) {
            $whereArray[] = ['order_goods_instalment.times', '=', $param['times']];
        }

        //根据用户手机号
        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_info.mobile', '=', $param['mobile']];
        }

        if(isset($param['is_instalment_list'])){
            $whereArray[] = ['order_info.order_status', '=', \App\Order\Modules\Inc\OrderStatus::OrderInService];
        }

        $result =  OrderGoodsInstalment::query()
            ->select('order_goods_instalment.*','order_info.mobile')
            ->where($whereArray)
            ->whereIn('order_goods_instalment.status',$statusArr)
            ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
            ->offset($offset)
            ->limit($pageSize)
			->orderBy('order_info.create_time','DESC')
			->orderBy('order_info.id','DESC')
			->orderBy('order_goods_instalment.term','ASC')
			->orderBy('order_goods_instalment.times','ASC')
            ->get();
        if (!$result) return false;
        return $result->toArray();
    }





    /**
     * 设置TradeNo
     */
    public static function setTradeNo($id, $business_no){

        if (!$id ) {
            return false;
        }

        if (!$business_no ) {
            return false;
        }

        $data = [
            'business_no'=>$business_no
        ];
        $result =  OrderGoodsInstalment::where(
            ['id'=>$id]
        )->update($data);

        if (!$result) return false;

        return true;

    }


    /*
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

        $result =  OrderGoodsInstalment::where($where)->update($data);
        if (!$result) return false;

        return true;
    }


    /**
     * 查询列表
     */
    public static function instalmentExport($param = []){
        $page       = isset($param['page']) ? $param['page'] : 1;
        $pageSize   = 500;
        $offset     = ($page - 1) * $pageSize;

        $whereArray = [];

        // 开始时间（可选）
        if( isset($param['begin_time']) && $param['begin_time'] != ""){
            $whereArray[] =  ['order_goods_instalment.term', '>=', $param['begin_time']];
        }

        // 开始时间（可选）
        if( isset($param['end_time']) && $param['end_time'] != ""){
            $whereArray[] =  ['order_goods_instalment.term', '<=', $param['end_time']];
        }


        //根据订单号
        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_goods_instalment.order_no', '=', $param['order_no']];
        }

        //根据分期状态
        if (isset($param['status']) && !empty($param['status'])) {
            $whereArray[] = ['order_goods_instalment.status', '=', $param['status']];
        }

        $whereArray[] = ['order_goods_instalment.status', '!=', \App\Order\Modules\Inc\OrderInstalmentStatus::CANCEL];

        $result =  OrderGoodsInstalment::query()
            ->select('order_goods.goods_name','order_goods.specs','order_goods.zuqi','order_goods_instalment.times','order_goods_instalment.amount','order_goods_instalment.status','order_goods.insurance','order_goods.insurance_cost','order_goods_instalment.payment_time')
            ->where($whereArray)
            ->leftJoin('order_goods', 'order_goods.order_no', '=', 'order_goods_instalment.order_no')
            ->offset($offset)
            ->limit($pageSize)
            ->orderBy('order_goods_instalment.term','ASC')
            ->orderBy('order_goods_instalment.times','ASC')
            ->get();

        if (!$result) return false;
        return $result->toArray();
    }


}