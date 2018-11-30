<?php
namespace App\Order\Modules\Repository;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderPayIncome;
use Illuminate\Support\Facades\DB;

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
        if(isset($param['keywords'])){
            if($param['kw_type'] == "order_no"){
                $param['order_no'] = $param['keywords'];
            }
            elseif($param['kw_type'] == "mobile"){
                $param['mobile'] = $param['keywords'];
            }
        }

        if (isset($param['name']) && !empty($param['name'])) {
            $whereArray[] = ['order_pay_income.name', '=', $param['name']];
        }

        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_pay_income.order_no', '=', $param['order_no']];
        }

        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_info.mobile', '=', $param['mobile']];
        }

        if (isset($param['appid']) && !empty($param['appid'])) {
            $whereArray[] = ['order_pay_income.appid', '=', $param['appid']];
        }

        if (isset($param['channel']) && !empty($param['channel'])) {
            $whereArray[] = ['order_pay_income.channel', '=', $param['channel']];
        }

        if (isset($param['business_type']) && !empty($param['business_type'])) {
            $whereArray[] = ['order_pay_income.business_type', '=', $param['business_type']];
        }

        if (isset($param['amount']) && !empty($param['amount'])) {
            $whereArray[] = ['order_pay_income.amount', '=', $param['amount']];
        }

        // 结束时间（可选），默认为为当前时间
        if( !isset($param['end_time']) || $param['end_time'] == ""){
            $param['end_time'] = date("Y-m-d 23:59:59");
        }else{
            $param['end_time'] = $param['end_time'] . " 23:59:59";
        }
        // 开始时间（可选）
        if( isset($param['begin_time']) && $param['begin_time'] != ""){
            if( $param['begin_time'] > $param['end_time'] ){
                return false;
            }
            $whereArray[] =  ['order_pay_income.create_time', '>', strtotime($param['begin_time'])];
            $whereArray[] =  ['order_pay_income.create_time', '<', strtotime($param['end_time'])];
        }else{
            $whereArray[] =  ['order_pay_income.create_time', '<', strtotime($param['end_time'])];
        }

        $result = OrderPayIncome::query()->where($whereArray)
            ->leftJoin('order_info', 'order_info.order_no', '=', 'order_pay_income.order_no')
            ->leftJoin('order_user_certified', 'order_user_certified.order_no', '=', 'order_pay_income.order_no')
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

        if(isset($param['keywords'])){
            if($param['kw_type'] == "order_no"){
                $param['order_no'] = $param['keywords'];
            }
            elseif($param['kw_type'] == "mobile"){
                $param['mobile'] = $param['keywords'];
            }
        }

        $whereArray = [];
        if (isset($param['name']) && !empty($param['name'])) {
            $whereArray[] = ['order_pay_income.name', '=', $param['name']];
        }

        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_pay_income.order_no', '=', $param['order_no']];
        }

        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_info.mobile', '=', $param['mobile']];
        }

        if (isset($param['appid']) && !empty($param['appid'])) {
            $whereArray[] = ['order_pay_income.appid', '=', $param['appid']];
        }

        if (isset($param['channel']) && !empty($param['channel'])) {
            $whereArray[] = ['order_pay_income.channel', '=', $param['channel']];
        }

        if (isset($param['business_type']) && !empty($param['business_type'])) {
            $whereArray[] = ['order_pay_income.business_type', '=', $param['business_type']];
        }

        if (isset($param['amount']) && !empty($param['amount'])) {
            $whereArray[] = ['order_pay_income.amount', '=', $param['amount']];
        }

        // 结束时间（可选），默认为为当前时间
        if( !isset($param['end_time']) || $param['end_time'] == ""){
            $param['end_time'] = date("Y-m-d 23:59:59");
        }else{
            $param['end_time'] = $param['end_time'] . " 23:59:59";
        }
        // 开始时间（可选）
        if( isset($param['begin_time']) && $param['begin_time'] != ""){
            if( $param['begin_time'] > $param['end_time'] ){
                return false;
            }
            $whereArray[] =  ['order_pay_income.create_time', '>', strtotime($param['begin_time'])];
            $whereArray[] =  ['order_pay_income.create_time', '<', strtotime($param['end_time'])];
        }else{
            $whereArray[] =  ['order_pay_income.create_time', '<', strtotime($param['end_time'])];
        }

        $result =  OrderPayIncome::query()
            ->select('order_pay_income.*','order_info.mobile','order_user_certified.realname')
            ->where($whereArray)
            ->leftJoin('order_info', 'order_info.order_no', '=', 'order_pay_income.order_no')
            ->leftJoin('order_user_certified', 'order_user_certified.order_no', '=', 'order_pay_income.order_no')
            ->offset($offset)
			->orderBy('order_pay_income.create_time','DESC')
            ->limit($pageSize)
            ->get();

        if (!$result) return [];
        return $result->toArray();
    }
    /**
     * 查询列表导出
     */
    public static function queryListExport($param = array(),$pagesize=5){
        $whereArray = [];

        if(isset($param['keywords'])){
            if($param['kw_type'] == "order_no"){
                $param['order_no'] = $param['keywords'];
            }
            elseif($param['kw_type'] == "mobile"){
                $param['mobile'] = $param['keywords'];
            }
        }

        if (isset($param['order_no']) && !empty($param['order_no'])) {
            $whereArray[] = ['order_pay_income.order_no', '=', $param['order_no']];
        }

        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_info.mobile', '=', $param['mobile']];
        }

        if (isset($param['appid']) && !empty($param['appid'])) {
            $whereArray[] = ['appid', '=', $param['appid']];
        }

        if (isset($param['channel']) && !empty($param['channel'])) {
            $whereArray[] = ['order_pay_income.channel', '=', $param['channel']];
        }

        if (isset($param['business_type']) && !empty($param['business_type'])) {
            $whereArray[] = ['order_pay_income.business_type', '=', $param['business_type']];
        }

        if (isset($param['amount']) && !empty($param['amount'])) {
            $whereArray[] = ['order_pay_income.amount', '=', $param['amount']];
        }

        //创建时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && (!isset($param['end_time']) || empty($param['end_time']))) {
            $whereArray[] = ['order_pay_income.create_time', '>=', strtotime($param['begin_time'])];
        }

        //创建时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['order_pay_income.create_time', '>=', strtotime($param['begin_time'])];
            $whereArray[] = ['order_pay_income.create_time', '<', (strtotime($param['end_time'])+3600*24)];
        }
        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }
        LogApi::debug("[queryListExport]查询条件",$whereArray );
        $result =  OrderPayIncome::query()
            ->select('order_pay_income.*','order_info.mobile','order_user_certified.realname')
            ->where($whereArray)
            ->leftJoin('order_info', 'order_info.order_no', '=', 'order_pay_income.order_no')
            ->leftJoin('order_user_certified', 'order_user_certified.order_no', '=', 'order_pay_income.order_no')
            ->orderBy('create_time','DESC')
            ->skip(($page - 1) * $pagesize)->take($pagesize)
            ->get()->toArray();
        return $result;
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