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
     * @$goods_no 商品编号
     */
    public static function getSumAmount($goods_no){
        if ($goods_no == "") return false;

        //获取订单商品信息
        $goodsObj = \App\Order\Modules\Repository\Order\Goods::getByGoodsNo($goods_no);
        if(empty($goodsObj)){
            return false;
        }
        $goodsInfo = $goodsObj->getData();

        //获取订单信息
        $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$goodsInfo['order_no']));
        if(!$orderInfo){
            return false;
        }

        // 花呗预授权 不参与 总金额计算
        if($orderInfo['pay_type'] == \App\Order\Modules\Inc\PayInc::FlowerFundauth){
            return [
                'amount'    => 0,
                'fenqishu'  => 0,
            ];
        }

        // 查询未完成分期
        $where[] = ['goods_no','=',$goods_no];

        $statusArr = [OrderInstalmentStatus::UNPAID,  OrderInstalmentStatus::FAIL];
        $result =  OrderGoodsInstalment::select(DB::raw("count(*) as fenqishu,sum(amount) as amount"))
            ->where($where)
            ->whereIn('status',$statusArr)
            ->first()->toArray();

        if (!$result) return false;
        return $result;
    }
    /**
     * 查询总数
     */
    public static function queryCount($param = []){

        $whereArray = self::ParamWhere($param);

        $statusArr = [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::SUCCESS, OrderInstalmentStatus::FAIL, OrderInstalmentStatus::CANCEL, OrderInstalmentStatus::PAYING];

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

        $whereArray = self::ParamWhere($param);

        $statusArr = [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::SUCCESS, OrderInstalmentStatus::FAIL, OrderInstalmentStatus::CANCEL, OrderInstalmentStatus::PAYING];

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

        $result =  OrderGoodsInstalment::query()
            ->select('order_goods_instalment.*','order_info.mobile','order_info.pay_type as order_pay_type')
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
        $instalmentList =  $result->toArray();

        // 订单支付方式
        foreach($instalmentList as &$item){
            $item['order_pay_type'] = \App\Order\Modules\Inc\PayInc::getPayName($item['order_pay_type']);
        }

        return $instalmentList;
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

        $whereArray = self::ParamWhere($param);

        $statusArr = [OrderInstalmentStatus::UNPAID, OrderInstalmentStatus::SUCCESS, OrderInstalmentStatus::FAIL, OrderInstalmentStatus::CANCEL, OrderInstalmentStatus::PAYING];

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


        $result =  OrderGoodsInstalment::query()
            ->select('order_goods.goods_name','order_goods.specs','order_goods.zuqi','order_goods_instalment.times','order_goods_instalment.amount','order_goods_instalment.status','order_goods.insurance','order_goods.insurance_cost','order_goods_instalment.payment_time')
            ->where($whereArray)
            ->whereIn('order_goods_instalment.status',$statusArr)
            ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
            ->leftJoin('order_goods', 'order_goods.order_no', '=', 'order_goods_instalment.order_no')
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
     * 延迟分期扣款时间 （只支持短租）
     * string    $order_no  订单号
     * string    $day       延期天数
     * return bool
     */
    public static function delayInstalment($order_no,$day){
        if ( $order_no == "") {
            return false;
        }
        if ( $day == "") {
            return false;
        }

        // 查询订单
        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($order_no);
        if (!$orderInfo) {
            LogApi::error('[delayInstalment]订单不存在：'.$order_no);
            return false;
        }

        // 订单不支持长租
        if($orderInfo['zuqi_type'] == \App\Order\Modules\Inc\OrderStatus::ZUQI_TYPE2){
            LogApi::error('[delayInstalment]延期不支持长租：'.$order_no);
            return false;
        }

        // 查询分期
        $instalmentInfo = self::getInfo(['order_no'=>$order_no]);
        if(!$instalmentInfo){
            LogApi::error('[delayInstalment]分期数据不存在：'.$order_no);
            return true;
        }

        if($instalmentInfo['withhold_day'] == ""){
            LogApi::error('[delayInstalment]分期数据错误：'.$order_no);
            return false;
        }

        $newWithholdDay = $instalmentInfo['withhold_day'] + 86400 * $day;

        $newTerm = date("Ym",$newWithholdDay);
        $newDay  = date("d",$newWithholdDay);

        $data = [
            'term'          => $newTerm,
            'day'           => $newDay,
            'withhold_day'  => $newWithholdDay,
        ];

        $result =  OrderGoodsInstalment::where(['order_no' => $order_no ])->limit(1)->update($data);
        if (!$result) return false;

        return true;
    }


    /**
     * 拼接where 条件
     */
    public static function ParamWhere( $param = [] ){
        $whereArray = [];

        //逾期天数
        if(isset($param['beoverdue_day']) && !empty($param['beoverdue_day'])){
            $beoverdue_day = strtoupper($param['beoverdue_day']);
            $whereArray = getBeoverduetime($beoverdue_day);
        }

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


        return $whereArray;
    }


    /**
     * 根据订单号 或者 商品编号 扣除未完成分期
     * @param order_no 订单号
     * @return boolean
     */
    public static function UnFinishWithhold($order_no){
        if ( $order_no == "") {
            return false;
        }

        $statusArr = [OrderInstalmentStatus::UNPAID,  OrderInstalmentStatus::FAIL];

        $instalmentList =  OrderGoodsInstalment::query()
            ->select('id')
            ->where([['order_no','=',$order_no]])
            ->whereIn('order_goods_instalment.status',$statusArr)
            ->get()
            ->toArray();
        if (!$instalmentList) return true;
        /**
         * 未完成分期 循环执行扣款操作
         */
        foreach($instalmentList as $item){
            \App\Order\Modules\Service\OrderWithhold::instalment_withhold($item['id']);
        }

        return true;
    }

    /***
     * 获取订单的扣款失败的总金额
     * @param array $params
     */
    public static function getFallInstalment(array $params){
        if (empty($params)) return false;
        $sum_amount = 0;
        $instalmentList =  OrderGoodsInstalment::query()
            ->where($params)
            ->sum("amount");
        if( !$instalmentList ){
            $instalmentList['aggregate'] = $sum_amount;
        }
        return $instalmentList;
    }
}