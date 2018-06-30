<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderPayIncome;

class OrderPayIncomeRepository
{

    /**
     * 创建记录
     */
    public static function create(array $params){
        return OrderPayIncome::create($params);
    }

    /**
     * 根据id查询信息
     */
    public static function getInfoById($id){
        if (empty($id)) return false;
        $result =  OrderPayIncome::query()->where([
            ['id', '=', $id],
        ])->first();
        if (!$result) return false;
        return $result->toArray();
    }

    /**
     * 查询收支信息
     */
    public static function getInfo($params){
        if (empty($params)) return false;
        $result =  OrderPayIncome::query()->where($params)->first();
        if (!$result) return false;
        return $result->toArray();
    }

    /**
     * 查询总数
     */
    public static function queryCount($param = []){
        $whereArray = [];
        if (isset($param['name']) && !empty($param['name'])) {
            $whereArray[] = ['name', '=', $param['name']];
        }

        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['goods_no', '=', $param['goods_no']];
        }

        if (isset($param['appid']) && !empty($param['appid'])) {
            $whereArray[] = ['appid', '=', $param['appid']];
        }

        if (isset($param['channel']) && !empty($param['channel'])) {
            $whereArray[] = ['channel', '=', $param['channel']];
        }

        if (isset($param['type']) && !empty($param['type'])) {
            $whereArray[] = ['type', '=', $param['type']];
        }

        if (isset($param['account']) && !empty($param['account'])) {
            $whereArray[] = ['account', '=', $param['account']];
        }

        $result = OrderPayIncome::query()->where($whereArray)
                ->count();
        return $result;
    }

    /**
     * 查询列表
     */
    public static function queryList($param = [], $additional = []){
        $page       = isset($additional['page']) ? $additional['page'] : 1;
        $pageSize   = isset($additional['limit']) ? $additional['limit'] : config("web.pre_page_size");
        $offset     = ($page - 1) * $pageSize;

        $whereArray = [];
        if (isset($param['name']) && !empty($param['name'])) {
            $whereArray[] = ['name', '=', $param['name']];
        }

        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['goods_no', '=', $param['goods_no']];
        }

        if (isset($param['appid']) && !empty($param['appid'])) {
            $whereArray[] = ['appid', '=', $param['appid']];
        }

        if (isset($param['channel']) && !empty($param['channel'])) {
            $whereArray[] = ['channel', '=', $param['channel']];
        }

        if (isset($param['business_type']) && !empty($param['business_type'])) {
            $whereArray[] = ['business_type', '=', $param['business_type']];
        }

        if (isset($param['amount']) && !empty($param['amount'])) {
            $whereArray[] = ['amount', '=', $param['amount']];
        }

        // 结束时间（可选），默认为为当前时间
        if( !isset($param['end_time']) || $param['end_time'] == ""){
            $param['end_time'] = date("Y-m-d H:i:s");
        }

        // 开始时间（可选）
        if( isset($param['begin_time']) && $param['begin_time'] != ""){
            if( $param['begin_time'] > $param['end_time'] ){
                return false;
            }
            $whereArray[] =  ['create_time', '>', strtotime($param['begin_time'])];
            $whereArray[] =  ['create_time', '<', strtotime($param['end_time'])];
        }else{
            $whereArray[] =  ['create_time', '<', strtotime($param['end_time'])];
        }

        $result =  OrderPayIncome::query()
            ->where($whereArray)
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        if (!$result) return false;
        return $result->toArray();
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

        $result =  OrderPayIncome::where($where)->update($data);
        if (!$result) return false;

        return true;
    }



}