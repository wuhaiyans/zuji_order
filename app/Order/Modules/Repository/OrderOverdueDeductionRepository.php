<?php
namespace App\Order\Modules\Repository;
use App\Lib\Common\LogApi;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderOverdueDeduction;

class OrderOverdueDeductionRepository
{
    /**
     *  获取逾期扣款列表
     * @author qinliping
     * @param  array $param  获取逾期扣款列表参数
     * ->paginate: 参数
     *  perPage:表示每页显示的条目数量
       columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
       pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
       page:表示查询第几页及查询页码
     */
    public static function getOverdueDeductionList($param = array(), $pagesize=5)
    {
        $whereArray = array();

        //根据手机号
        if (isset($param['kw_type']) && $param['kw_type']=='mobile' && !empty($param['keywords']))
        {
            $whereArray[] = ['mobile', '=', $param['keywords']];
        }
        //根据订单号
        elseif (isset($param['kw_type']) && $param['kw_type']=='order_no' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_no', '=', $param['keywords']];
        }

        //订单来源
        if (isset($param['app_id']) && !empty($param['app_id'])) {
            $whereArray[] = ['app_id', '=', $param['app_id']];
        }


        //扣款状态
        if (isset($param['deduction_status']) && !empty($param['deduction_status'])) {
            $whereArray[] = ['deduction_status', '=', $param['deduction_status']];
        }

        //回访标识
        if (isset($param['visit_id'])) {
           $whereArray[] = ['visit_id', '=', $param['visit_id']];
        }
        //长短租类型
        if (isset($param['zuqi_type'])) {
            $whereArray[] = ['zuqi_type', '=', $param['zuqi_type']];
        }

        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }

        $orderList = DB::table('order_overdue_deduction')
            ->select('order_overdue_deduction.*')
            ->where($whereArray)
            ->orderBy('create_time', 'DESC')
            ->paginate($pagesize,$columns = ['*'], $pageName = 'page', $page);
        if( !$orderList ){
            return [];

        }
        return $orderList;
    }

    /**
     *  导出获取逾期扣款列表
     * @author qinliping
     * @param  array $param  获取逾期扣款列表参数
     * @param  paginate: 参数
     */
    public static function overdueDeductionListExport($param = array(), $pagesize=5)
    {
        $whereArray = array();
        $isUncontact = 0;

        //根据手机号
        if (isset($param['kw_type']) && $param['kw_type']=='mobile' && !empty($param['keywords']))
        {
            $whereArray[] = ['mobile', '=', $param['keywords']];
        }
        //根据订单号
        elseif (isset($param['kw_type']) && $param['kw_type']=='order_no' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_no', '=', $param['keywords']];
        }

        //订单来源
        if (isset($param['app_id']) && !empty($param['app_id'])) {
            $whereArray[] = ['app_id', '=', $param['app_id']];
        }


        //扣款状态
        if (isset($param['deduction_status']) && !empty($param['deduction_status'])) {
            $whereArray[] = ['deduction_status', '=', $param['deduction_status']];
        }

        //回访标识
        if (isset($param['visit_id'])) {
            $whereArray[] = ['visit_id', '=', $param['visit_id']];
        }
        //长短租类型
        if (isset($param['zuqi_type'])) {
            $whereArray[] = ['zuqi_type', '=', $param['zuqi_type']];
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }

        $overdueList = DB::table('order_overdue_deduction')
            ->select('*')
            ->where($whereArray)
            ->orderBy('create_time', 'DESC')
            ->skip(($page - 1) * $pagesize)->take($pagesize)
            ->get();
        $overdueListArray  = array_column(objectToArray($overdueList),NULL,'order_no');
        if( !$overdueListArray ){
            return [];

        }
        return $overdueListArray;
    }

    /**
     * 逾期扣款详情
     * array    $where
     * return array
     */
    public static function info($where){
        if ( $where == [] ) {
            return false;
        }

        $result =  OrderOverdueDeduction::where($where)->first();
        if (!$result) return false;
        return $result->toArray();
    }
    /**
     * 逾期扣款数据
     * array    $where
     * return array
     */
    public static function getOverdueInfo(){
        $result =  DB::table('order_overdue_deduction')
            ->select('order_no')->get();
        if (!$result) return false;
        $resultArray = objectToArray($result);

        return $resultArray;
    }

    /**
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

        $result =  OrderOverdueDeduction::where($where)->update($data);
        if (!$result) return false;

        return true;
    }

    /**
     * 获取逾期订单信息
     * @param  $orderNo 订单编号
     * @return array
     */
    public static function getOverdueOrderDetail($orderNo){
        if(!isset( $orderNo )){
            return false;
        }
        $where[] = ['order_info.order_no','=',$orderNo];
        $order_result = DB::table('order_info')
            ->leftJoin('order_goods', 'order_info.order_no', '=', 'order_goods.order_no')
            ->leftJoin('order_user_certified', 'order_info.order_no', '=', 'order_user_certified.order_no')
            ->where($where)
            ->select('order_info.mobile','order_info.user_id','order_info.appid','order_info.zuqi_type','order_info.create_time','order_goods.yajin','order_goods.surplus_yajin','order_goods.goods_name','order_user_certified.realname')
            ->first();

        if(!$order_result){
            return [];
        }
        return objectToArray($order_result);
    }

    /**
     * 创建逾期记录
     * @param array $data
     */
    public static function createOverdue(array $data){
        $createResult = OrderOverdueDeduction::query()->insert($data);
        if( !$createResult ){
            return false;
        }
        return $createResult;
    }
}