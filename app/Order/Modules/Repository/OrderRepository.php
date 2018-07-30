<?php
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use App\Lib\Goods\Goods;
use App\Order\Models\Order;
use App\Order\Models\OrderCoupon;
use App\Order\Models\OrderExtend;
use App\Order\Models\OrderGoodsDelivery;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserCertified;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Service\OrderInstalment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
     * 查询近两天匹配度查询
     * @param $data
     *      'order_id'=>'' //【必须】当前订单ID
     *      'user_id' =>'' //【必须】下单用户
     *      'order_no' =>'' //【必须】下单订单编号
     *      'create_time'=>''//【必须】下单时间
     */

    public function similarOrderAddress($data){
        //查询近7天 订单地址相似度匹配>70% 的
        $this->district_service = $this->load->service('admin/district');
        $this->order2_similar_address = $this->load->table('order2/order2_similar_address');
        $start = $data['create_time']-2*86400;
        $end   = $data['create_time'];

        $whereArray['user_id'] =['<>', $data['user_id']];
        $whereArray['create_time'] =[ 'BETWEEN', array($start ,$end)];
        $whereArray['status'] =['in', [OrderStatus::OrderPaying,OrderStatus::OrderPayed,OrderStatus::OrderInStock]];

        $orderList = DB::table('order_info')
            ->leftJoin('order_userinfo', 'order_info.order_no', '=', 'order_userinfo.order_no')
            ->where($whereArray)
            ->select('order_info.*','order_userinfo.*');
    }

    /**
     * 待确认订单的数量
     * @return int
     */

    public static function getWaitingConfirmCount(){
        $whereArray[] = ['order_status', '=', OrderStatus::OrderPayed];
        return Order::where($whereArray)->count();
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
     *
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
     * 获取订单扩展表状态
     * @param $orderNo
     * @return array|bool
     */

    public static function getOrderExtends($orderNo,$field_name=""){
        if (empty($orderNo)) return false;
        $whereArray[] = ['order_no', '=', $orderNo];

        if($field_name !=""){
            $whereArray[] = ['field_name', '=', $field_name];
        }
        $orderExtends = OrderExtend::query()->where($whereArray)->get();
        if (!$orderExtends) return [];
        return $orderExtends->toArray();
    }
    /**
     * 根据订单id和商品id查询设备列表
     * heaven
     * @param $order_no 订单编号
     * @param $goods_no 商品编号  可选
     * @return array|bool
     *
     */

    public static function getGoodsListByGoodsId($params){
        if (empty($params['order_no'])) return false;
        $where[]=['order_no', '=', $params['order_no']];
        if(isset($params['goods_no'])){
            $where[]=['goods_no', '=', $params['goods_no']];
        }
        $orderGoodData =  OrderGoods::query()->where($where)->get();
        if (!$orderGoodData) return false;
        return $orderGoodData->toArray();
    }


    /**
     * 根据订单号查询设备号信息
     * heaven
     * @param $orderNo 订单编号
     * @return bool
     */

    public static function getGoodsDeliverInfo($orderNo){
        if (empty($orderNo)) return false;
        $orderGoodsDeliveryData =  OrderGoodsDelivery::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderGoodsDeliveryData) return false;
        return $orderGoodsDeliveryData->toArray();
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
        $order->complete_time = time();
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
                ->leftJoin('order_user_address', function ($join) {
                    $join->on('order_info.order_no', '=', 'order_user_address.order_no');
                })
                ->leftJoin('order_user_certified', function ($join) {
                    $join->on('order_info.order_no', '=', 'order_user_certified.order_no');
                })
                ->where('order_info.order_no', '=', $param['order_no'])
                ->select('order_info.*','order_user_address.*','order_user_certified.*')
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

    /**
     *
     * 更新订单冻结状态
     * heaven
     * @param $orderNo 订单编号
     * @param $freezeStatus 冻结状态
     * @return bool
     */

    public static function orderFreezeUpdate($orderNo, $freezeStatus){

        if (empty($orderNo) || !isset($freezeStatus)) {
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
     * 查询所有订单信息
     * @param $param array
     * [
     *      'order_status' =>'',//订单状态
     *      'now_time' =>'',//查找小于当前时间
     * ]
     * @return array|bool
     */
    public static function getOrderAll($param =[]){

        $whereArray =[];
        //订单状态
        if (isset($param['order_status']) && !empty($param['order_status'])) {
            $whereArray[] = ['order_status', '=', $param['order_status']];
        }
        //订单创建时间
        if (isset($param['now_time']) && !empty($param['now_time'])) {
            $whereArray[] = ['create_time', '<=', $param['now_time']];
        }
        $orderData =Order::query()->where($whereArray)->get()->toArray();
        return $orderData ?? false;

    }



    /**
     *  获取客户端订单列表
     *  heaven
     * ->paginate: 参数
     *  perPage:表示每页显示的条目数量
    columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
    pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
    page:表示查询第几页及查询页码
     * @param array $param  获取订单列表参数
     */
    public static function getClientOrderList($param = array(), $pagesize=5)
    {
        $whereArray = array();
        //根据用户id
        $whereArray[] = ['order_info.user_id', '=', $param['uid']];
        //订单状态
        if (isset($param['order_status']) && !empty($param['order_status'])) {
            $whereArray[] = ['order_info.order_status', '=', $param['order_status']];
        }
        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }

        $page = 1;
        if (isset($param['page'])){
            $page = intval($param['page']);
        }
        $orderList = DB::table('order_info')
            ->select('order_info.*','order_user_address.*')
            ->join('order_user_address',function($join){
                $join->on('order_info.order_no', '=', 'order_user_address.order_no');
            }, null,null,'inner')
            ->where($whereArray)
            ->orderBy('order_info.create_time', 'DESC')
            ->paginate($pagesize,$columns = ['*'], $pageName = 'page', $page);

        //dd(objectToArray($orderList));
        return $orderList;

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
    public static function getOrderList($param = array(), $pagesize=5)
    {
        $whereArray = array();
		$orWhereArray = array();
//        $visitWhere = array();
        //根据用户id
        if (isset($param['user_id']) && !empty($param['user_id'])) {

            $whereArray[] = ['order_info.user_id', '=', $param['user_id']];
        }
        //根据订单编号
        if (isset($param['order_no']) && !empty($param['order_no'])) {

            $whereArray[] = ['order_info.order_no', '=', $param['order_no']];
        }

        //根据手机号
        if (isset($param['kw_type']) && $param['kw_type']=='mobile' && !empty($param['keywords']))
        {
            $orWhereArray[] = ['order_info.mobile', '=', $param['keywords'],'or'];
            $orWhereArray[] = ['order_user_address.consignee_mobile', '=', $param['keywords'],'or'];
        }
        //根据订单号
        elseif (isset($param['kw_type']) && $param['kw_type']=='order_no' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_info.order_no', '=', $param['keywords']];
        }

        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_user_address.consignee_mobile', '=', $param['keywords']];
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
            $whereArray[] = ['order_info.order_status', '=', $param['order_status']];
        }

        //下单时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && (!isset($param['end_time']) || empty($param['end_time']))) {
            $whereArray[] = ['order_info.create_time', '>=', strtotime($param['begin_time'])];
        }

        //下单时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['order_info.create_time', '>=', strtotime($param['begin_time'])];
            $whereArray[] = ['order_info.create_time', '<', (strtotime($param['end_time'])+3600*24)];
        }

        if (isset($param['visit_id'])) {
            $whereArray[] = ['order_info_visit.visit_id', '=', $param['visit_id']];
        }


        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }
//        //dd($whereArray);

//        DB::table('order_info')
//            ->join('order_user_address',function($join){
//                $join->on('order_info.order_no', '=', 'order_user_address.order_no')
//                    ->where('b.status','=','SUCCESS')
//                    ->where('b.type','=','UNLOCK');
//            }, null,null,'left')
//            ->where('a.id','>',1)
//            ->get();
        

//        sql_profiler();
        $orderList = DB::table('order_info')
            ->select('order_info.*','order_user_address.*','order_info_visit.visit_id','order_info_visit.visit_text','order_delivery.logistics_no')
            ->join('order_user_address',function($join){
                $join->on('order_info.order_no', '=', 'order_user_address.order_no');
            }, null,null,'inner')
            ->join('order_info_visit',function($join){
                $join->on('order_info.order_no', '=', 'order_info_visit.order_no');
            }, null,null,'left')
            ->join('order_delivery',function($join){
                $join->on('order_info.order_no', '=', 'order_delivery.order_no');
            }, null,null,'left')
            ->where($whereArray)
            ->where($orWhereArray)
            ->orderBy('order_info.create_time', 'DESC')
            ->orderBy('order_info_visit.id','desc')
            ->paginate($pagesize,$columns = ['order_info.order_no'], 'page', $param['page']);

//        $orderList = DB::table('order_info')
//            ->leftJoin('order_user_address', 'order_info.order_no', '=', 'order_user_address.order_no')
//            ->leftJoin('order_info_visit','order_info.order_no', '=', 'order_info_visit.order_no')
//            ->where($whereArray)


        //dd(objectToArray($orderList));
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
    /**
     * 根据订单编号获取认证信息
     *
     */
    public static function getUserCertified($order_no){
        if(empty($order_no)){
            return false;
        }
        $where[]=['order_no','=',$order_no];
        $getUserCertified=OrderUserCertified::where($where)->first();
        if(!$getUserCertified){
            return false;
        }
        return $getUserCertified->toArray();

    }

    /**
     * 根据用户id查询用户最新下单订单信息+商品信息
     * @params $user_id //用户id
     */
    public static function getUserNewOrder($user_id){
        if(empty($user_id)){
            return false;
        }
        $where[]=['user_id','=',$user_id];
        $where[]=['order_status', '<=', OrderStatus::OrderInService];
        $order =  Order::query()->where($where)->orderBy('create_time','desc')->first();
        if(!$order){
            return false;
        }
        $orderArr = $order->toArray();
        $goods = \App\Order\Modules\Repository\OrderGoodsRepository::getGoodsByOrderNo($orderArr['order_no']);
        if(!$goods){
            return false;
        }
        $goodsArr = $goods->toArray();
        //计算免押金
        $goodsArr[0]['mianyajin'] = normalizeNum($goodsArr[0]['goods_yajin'] - $goodsArr[0]['yajin']);
        $specsArr=explode(';', $goodsArr[0]['specs']);
        $specs = '';
        foreach($specsArr as $val){
            $specs .= substr($val,strpos($val,':')+1).'/';
        }
        $specs = substr($specs,0,-1);
        $goodsArr[0]['specs'] = $specs;
        //修改数据格式
        $data = [
            'orderArr'=>$orderArr,
            'goodsArr'=>$goodsArr,
        ];
        return $data;

    }
}