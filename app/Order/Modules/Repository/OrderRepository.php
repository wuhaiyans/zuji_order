<?php
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use App\Lib\Goods\Goods;
use App\Order\Models\Order;
use App\Order\Models\OrderCoupon;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Service\OrderInstalment;
use Illuminate\Support\Facades\DB;

class OrderRepository
{

    protected $order;


    public function __construct()
    {
        $this->order = new Order();
    }
    public function add($data){
        return $this->order->insertGetId($data);
    }
    /**
     * 发货操作
     * @param $orderNo
     * @return boolean
     */
    public static function delivery($orderNo)
    {
        if (empty($orderNo)) {
            return false;
        }
        $data['order_status'] =OrderStatus::OrderDeliveryed;
        $data['delivery_time'] =time();
        return Order::where('order_no','=',$orderNo)->update($data);

    }
    /**
     * 保存渠道信息
     * @param $orderNo
     * @return boolean
     */
    public static function updateChannel($orderNo,$channelId)
    {
        if (empty($orderNo)) {
            return false;
        }
        $data['channel_id'] =$channelId;
        return Order::where('order_no','=',$orderNo)->update($data);

    }
    /**
     * 确认收货操作
     * @param $orderNo
     * @return boolean
     */
    public static function deliveryReceive($orderNo)
    {
        if (empty($orderNo)) {
            return false;
        }
        $data['order_status'] =OrderStatus::OrderInService;
        $data['confirm_time'] =time();
        return Order::where('order_no','=',$orderNo)->update($data);

    }
    /**
     * 确认订单操作
     * @param $orderNo
     * @param $remark
     * @return boolean
     */
    public static function confirmOrder($orderNo,$remark)
    {
        if (empty($orderNo)) {
            return false;
        }
        if (empty($remark)) {
            return false;
        }
        $data['order_status'] = OrderStatus::OrderInStock;
        $data['remark'] =$remark;
        $data['confirm_time'] =time();
        return Order::where('order_no','=',$orderNo)->update($data);

    }

    /**
     *  保存支付交易号
     */
    public static function updateTrade($orderNo, $trade_no,$userId=''){

        if (empty($orderNo)) {
            return false;
        }
        if (empty($trade_no)) {
            return false;
        }
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        if (!empty($userId)) {

            $whereArray[] = ['user_id', '=', $userId];
        }
        $order =  Order::where($whereArray)->first();
        //return $order->toArray();
        if (!$order) return false;
        $order->trade_no = $trade_no;
        if ($order->save()) {
            return true;
        } else {
            return false;
        }

    }


    /**
     *
     * 根据订单id订单信息
     * heaven
     * @param $orderNo 订单编号
     * @param string $userId 用户id
     * @return array|bool
     */
    public static function getInfoById($orderNo,$userId=''){
            if (empty($orderNo)) return false;
            $whereArray = array();
            $whereArray[] = ['order_no', '=', $orderNo];
            if (!empty($userId)) {

                $whereArray[] = ['user_id', '=', $userId];
            }
            $order =  Order::query()->where($whereArray)->first();
            if (!$order) return false;
            return $order->toArray();
    }

    /**
     * 根据订单编号查询有关订单使用的优惠券
     * @param $orderNo
     * @return array|bool
     */

    public static function getCouponByOrderNo($orderNo){
        if (empty($orderNo)) return false;
        $orderCoupon =  OrderCoupon::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderCoupon) return false;
        return $orderCoupon->toArray();
    }



    /**
     * 根据订单id查询设备列表
     * heaven
     * @param $orderNo 订单编号
     * @return array|bool
     *
     */

    public static function getGoodsListByOrderId($orderNo){
        if (empty($orderNo)) return false;
        $orderGoodData =  OrderGoods::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderGoodData) return false;
        return $orderGoodData->toArray();
    }



    /**
     * 根据订单号查询设备号信息
     * heaven
     * @param $orderNo 订单编号
     * @return bool
     */

    public static function getGoodsExtendInfo($orderNo){
        if (empty($orderNo)) return false;
        $orderGoodExtendData =  OrderGoodsExtend::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderGoodExtendData) return false;
        return $orderGoodExtendData->toArray();
    }
    /**
     *
     * 查询订单是否可以支付
     *return boolean
     */
    public static function isPay($orderNo,$userId)
    {
        if (empty($orderNo)) return false;
        $orderData = Order::query()->where([
            ['user_id', '=', $userId],
            ['order_no', '=', $orderNo],
        ])->first()->toArray();
        if(empty($orderData)){
            return false;
        }
        if($orderData['order_status']!= OrderStatus::OrderWaitPaying || $orderData['pay_time'] >0){
            return false;
        }
        if(($orderData['order_amount']+$orderData['order_yajin'])<=0){
            return false;
        }
        return true;

    }


    /**
     * 查询未完成的订单
     * heaven
     * @param $userId 用户id
     * @return bool
     */
    public static function unCompledOrder($userId)
    {
        if (empty($userId)) return false;
        $orderData = Order::query()->where([
            ['user_id', '=', $userId],
            ['order_status', '<=', OrderStatus::OrderInService],
        ])->get()->toArray();
        return !empty($orderData) ?? false;

    }



    /**
     * heaven
     * @param $orderNo 订单编号
     * @param string $userId 用户id
     * @return bool
     */
    public static function closeOrder($orderNo, $userId=''){

        if (empty($orderNo)) {

            return false;
        }
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        if (!empty($userId)) {

            $whereArray[] = ['user_id', '=', $userId];
        }
        $order =  Order::where($whereArray)->first();
        if (!$order) return false;
        $order->order_status = OrderStatus::OrderCancel;
        if ($order->save()) {
            return true;
        } else {

            return false;
        }

    }


    /**
     * @param $where
     * @return array|bool
     * 获取订单信息
     */
    public function get_order_info($where){
        $orderNo=$where['order_no'];
        $order =  Order::where([
            ['order_no', '=', $orderNo],
        ])->get()->toArray();
        if (!$order){
            return false;
        }else{
            return $order;
        }
    }


    /**
     * 根据订单号获取用户优惠券信息
     * Author: heaven
     * @param $orderNo
     * @return array|bool
     */
    public static function getCouponListByOrderId($orderNo)
    {
        if (empty($orderNo)) return false;
        $orderCouponData = OrderCoupon::query()->where([
            ['order_no', '=', $orderNo],
        ])->get()->toArray();
        return $orderCouponData ?? false;

    }

    /**
     * heaven
     * 获取订单详情
     * @param array $param  orderNo 订单号
     * @return array|bool
     */
    public static function getOrderInfo($param = array())
    {
        if (empty($param)) {
            return false;
        }
        if (isset($param['order_no']) && !empty($param['order_no']))
        {
            $orderData = DB::table('order_info')
                ->leftJoin('order_userinfo', function ($join) {
                    $join->on('order_info.order_no', '=', 'order_userinfo.order_no');
                })
                ->where('order_info.order_no', '=', $param['order_no'])
                ->select('order_info.*','order_userinfo.*')
                ->first();

            return !empty($orderData)?objectToArray($orderData):false;
        }
        return false;

    }
    //更新订单状态-申请退货
    public static function order_update($order_no){
        $data['freeze_type']=OrderFreezeStatus::Refund;
        if(Order::where('order_no', '=', $order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //更新订单状态-审核拒绝
    public static function deny_update($order_no){
        $data['freeze_type']=OrderFreezeStatus::Non;
        if(Order::where('order_no', '=', $order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }

    public static function orderPayStatus(string $orderNo,int $payStatus){
        if(empty($orderNo) || empty($payStatus)){
            return false;
        }
        $data['order_status'] = $payStatus;
        $data['pay_time'] =time();
        $data['update_time'] = time();
        return Order::where('order_no','=',$orderNo)->update($data);
    }

    /**
     *
     * 更新订单冻结状态
     * heaven
     * @param $orderNo 订单编号
     * @param $freezeStatus 冻结状态
     * @return bool
     */

    public static function orderFreezeUpdate($orderNo, $freezeStatus){

        if (empty($orderNo) || empty($freezeStatus)) {
            return false;
        }

        //查询传入的冻结状态是否在范围内
        if (!in_array($freezeStatus, array_keys(OrderFreezeStatus::getStatusList()))) {
            return false;
        }
        $data['freeze_type']    =   $freezeStatus;

        if(Order::where('order_no', '=', $orderNo)->update($data)){
            return true;
        }else{
            return false;
        }

    }



    /**
     *  获取订单列表
     *  heaven
     * ->paginate: 参数
     *  perPage:表示每页显示的条目数量
        columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
        pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
        page:表示查询第几页及查询页码
     * @param array $param  获取订单列表参数
     */
    public static function getOrderList($param = array(), $pagesize=2)
    {
        $whereArray = array();
        //根据用户id
        if (isset($param['user_id']) && !empty($param['user_id'])) {

            $whereArray[] = ['order_info.user_id', '=', $param['user_id']];
        }
        //根据订单编号
        if (isset($param['order_no']) && !empty($param['order_no'])) {

            $whereArray[] = ['order_info.order_no', '=', $param['order_no']];
        }

        //根据手机号
        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_userinfo.mobile', '=', $param['mobile']];
        }

        //应用来源ID
        if (isset($param['order_appid']) && !empty($param['order_appid'])) {
            $whereArray[] = ['order_info.appid', '=', $param['order_appid']];
        }

        //支付类型
        if (isset($param['pay_type']) && !empty($param['pay_type'])) {
            $whereArray[] = ['order_info.pay_type', '=', $param['pay_type']];
        }

        //订单状态
        if (isset($param['order_status']) && !empty($param['order_status'])) {
            $whereArray[] = ['order_info.appid', '=', $param['order_appid']];
        }

        //下单时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && (!isset($param['end_time']) || empty($param['end_time']))) {
            $whereArray[] = ['order_info.create_time', '>=', $param['begin_time']];
        }

        //下单时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['order_info.create_time', '>=', $param['begin_time']];
            $whereArray[] = ['order_info.create_time', '<=', $param['end_time']];
        }

        if (isset($param['visit_id']) && !empty($param['visit_id']) ) {
            $whereArray[] = ['order_info_extend.visit_id', '=', $param['visit_id']];
        }
        
        $orderList = DB::table('order_info')
            ->leftJoin('order_userinfo', 'order_info.order_no', '=', 'order_userinfo.order_no')
            ->leftJoin('order_info_extend','order_info.order_no', '=', 'order_info_extend.order_no')
            ->where($whereArray)
            ->select('order_info.*','order_userinfo.*','order_info_extend.visit_id')
            ->paginate($pagesize,$columns = ['*'], $pageName = 'page', $param['page']);
        return $orderList;

    }

    //更新订单状态-订单完成
    public static function orderClose($order_no){
        $data['order_status']=OrderStatus::OrderCompleted;
        if(Order::where('order_no', '=', $order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }
}