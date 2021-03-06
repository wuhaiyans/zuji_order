<?php
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use App\Lib\Goods\Goods;
use App\Lib\Order\OrderInfo;
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

    public static function getGoodsListByOrderId($orderNo,$coulumn='*'){
        if (empty($orderNo)) return false;

        $orderGoodData =  OrderGoods::query()->where([
            ['order_no', '=', $orderNo],
        ])->select($coulumn)->get();
        if (!$orderGoodData) return false;
        return $orderGoodData->toArray();
    }


    /**
     *
     * 根据多个订单id查询设备列表
     * heaven
     * @param $orderNo 订单编号
     * @return array|bool
     *
     */

    public static function getGoodsListByOrderIdArray($orderIds,$coulumn='*'){
        if (empty($orderIds)) return false;
        $orderGoodData =  DB::connection('mysql_read')->table('order_goods')->whereIn('order_no', $orderIds)->select($coulumn)->get();
        if (!$orderGoodData) return false;
        return objectToArray($orderGoodData->toArray());
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
     * 根据身份证，查询未完成的订单
     * @param $certNo
     * @return bool
     */
    public static function unCompledOrderByCertNo($certNo)
    {
		// 
        $sql ="select count(O.order_no) AS count from order_info O left join order_user_certified UC ON UC.order_no=O.order_no where O.order_status<=6 and UC.cret_no='".$certNo."'";
        $orderData = DB::selectOne($sql);
        $orderData =objectToArray($orderData);
		// count==0时，表示没有未完成订单了
		if( isset($orderData['count']) && $orderData['count']==0){
			return false;
		}
		// 有未完成订单
        return true;

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
                ->select('order_info.*','order_user_address.*','order_user_certified.certified'
                    ,'order_user_certified.certified_platform','order_user_certified.credit','order_user_certified.realname','order_user_certified.cret_no'
                    ,'order_user_certified.card_img','order_user_certified.deposit_detail','order_user_certified.deposit_msg','order_user_certified.matching'
                )
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
        //渠道来源
        if (isset($param['appid']) && !empty($param['appid'])) {
            if (in_array($param['appid'], config('web.mini_appid'))) {
                $whereArray[] = ['order_info.appid', '=', $param['appid']];
            }
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
     *  获取前台订单列表
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

        //应用渠道
        if (isset($param['order_appid']) && !empty($param['order_appid'])) {
            $whereArray[] = ['order_info.channel_id', '=', $param['order_appid']];
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

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }

        $count = DB::table('order_info')
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
            ->orderBy('order_info.id', 'DESC')
            ->count();



//        sql_profiler();
        $orderList = DB::table('order_info')
            ->select('order_info.order_no')
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
//            ->paginate($pagesize,$columns = ['order_info.order_no'], 'page', $param['page']);
//            ->forPage($page, $pagesize)
//
            ->skip(($page - 1) * $pagesize)->take($pagesize)
            ->get();

        $orderArray = objectToArray($orderList);

        if ($orderArray) {
            $orderIds = array_column($orderArray,"order_no");
//           dd($orderIds);
//            sql_profiler();
            $orderList =  DB::table('order_info')
                ->select('order_info.*','order_user_address.*','order_info_visit.visit_id','order_info_visit.visit_text','order_delivery.logistics_no')
                ->whereIn('order_info.order_no', $orderIds)
                ->join('order_user_address',function($join){
                $join->on('order_info.order_no', '=', 'order_user_address.order_no');
            }, null,null,'inner')
                ->join('order_info_visit',function($join){
                    $join->on('order_info.order_no', '=', 'order_info_visit.order_no');
                }, null,null,'left')
                ->join('order_delivery',function($join){
                    $join->on('order_info.order_no', '=', 'order_delivery.order_no');
                }, null,null,'left')
                ->orderBy('order_info.create_time', 'DESC')
                ->orderBy('order_info_visit.id','desc')
                ->get();

            $orderArrays['data'] = objectToArray($orderList);
            $orderArrays['total'] = $count;
            $orderArrays['last_page'] = ceil($count/$pagesize);

            return $orderArrays;
//            leftJoin('order_user_address', 'order_info.order_no', '=', 'order_user_address.order_no')

        }
        return false;

    }




    /**
     *  获取后台订单列表
     *  heaven
     * ->paginate: 参数
     *  perPage:表示每页显示的条目数量
    columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
    pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
    page:表示查询第几页及查询页码
     * @param array $param  获取订单列表参数
     */
    public static function getAdminOrderList($param = array(), $pagesize=5)
    {
        $whereArray = array();
        $orWhereArray = array();
        $whereInArray = array();
        $isUncontact = 0;
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
            $whereArray[] = ['order_info.mobile', '=', $param['keywords']];
        }
        //根据订单号
        elseif (isset($param['kw_type']) && $param['kw_type']=='order_no' && !empty($param['keywords']))
        {
            $whereArray[] = ['order_info.order_no', '=', $param['keywords']];
        }

        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['order_user_address.consignee_mobile', '=', $param['mobile']];
        }

        //应用渠道
        if (isset($param['order_appid']) && !empty($param['order_appid'])) {
            $whereArray[] = ['order_info.channel_id', '=', $param['order_appid']];
        }

        //第三方渠道类型
        if (isset($param['channel_id']) && !empty($param['channel_id'])) {

            $whereInArray = $param['channel_id'];
        }

        //支付类型
        if (isset($param['pay_type']) && !empty($param['pay_type'])) {
            $whereArray[] = ['order_info.pay_type', '=', $param['pay_type']];
        }

        //风控审核状态
        if (isset($param['risk_check']) && !empty($param['risk_check'])) {
            $whereArray[] = ['order_info.risk_check', '=', $param['risk_check']];
        }

        //订单状态
        if (isset($param['order_status']) && !empty($param['order_status'])) {
            if ($param['order_status'] == OrderStatus::validOrder) {

//                $whereArray[]  = [OrderStatus::OrderInService, OrderStatus::OrderDeliveryed,OrderStatus::OrderInStock, OrderStatus::OrderPayed, OrderStatus::OrderCompleted];

                $whereArray[] = ['order_info.order_status', '>=', OrderStatus::OrderPayed];
                $whereArray[] = ['order_info.order_status', '<=', OrderStatus::OrderCompleted];
                $whereArray[] = ['order_info.order_status', '!=', OrderStatus::OrderCancel];
                $whereArray[] = ['order_info.order_status', '!=', OrderStatus::OrderClosedRefunded];
            } else {

                $whereArray[] = ['order_info.order_status', '=', $param['order_status']];
            }

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
            if (empty($param['visit_id'])) {
                $isUncontact = 1;
            } else {

                $whereArray[] = ['order_info_visit.visit_id', '=', $param['visit_id']];
            }

        }
        //长短租类型
        if (isset($param['zuqi_type'])) {
            $whereArray[] = ['order_info.zuqi_type', '=', $param['zuqi_type']];
        }

        if (isset($param['size'])) {
            $pagesize = $param['size'];
        }

        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }

       // sql_profiler();

            if (empty($whereArray) && empty($isUncontact) && empty($whereInArray)) {
                $whereArray[] = ['order_info.create_time', '>', 0];
                $count = DB::table('order_info')
                    ->select(DB::raw('count(order_info.order_no) as order_count'))
//                    ->join('order_user_address',function($join){
//                        $join->on('order_info.order_no', '=', 'order_user_address.order_no');
//                    }, null,null,'inner')
                    ->first();

            } else {

                $whereArray[] = ['order_info.create_time', '>', 0];
                $count = DB::table('order_info')
                    ->select(DB::raw('count(order_info.order_no) as order_count'))
                    ->join('order_user_address',function($join){
                        $join->on('order_info.order_no', '=', 'order_user_address.order_no');
                    }, null,null,'inner')
                    ->join('order_info_visit',function($join){
                        $join->on('order_info.order_no', '=', 'order_info_visit.order_no');
                    }, null,null,'left')
                    ->join('order_delivery',function($join){
                        $join->on('order_info.order_no', '=', 'order_delivery.order_no');
                    }, null,null,'left')
                    ->when(!empty($whereInArray),function($join) use ($whereInArray) {
                        return $join->whereIn('order_info.channel_id', $whereInArray);
                    })
                    ->when(!empty($whereArray),function($join) use ($whereArray) {
                        return $join->where($whereArray);
                    })
                    ->when(!empty($isUncontact),function($join) {
                        return $join->where(function ($join) {  //闭包返回的条件会包含在括号中
                            $join-> whereNull('order_info_visit.visit_id')
                                ->orWhere([
                                    ['order_info_visit.visit_id', '0']
                                ]);
                        });
                    })
                    ->first();

            }

            $count = objectToArray($count)['order_count'];
            if (!isset($param['count'])) {
//                    sql_profiler();
                    $orderList = DB::table('order_info')
                        ->select(DB::raw('distinct(order_info.order_no) as order_no,order_info.create_time'))
                        ->join('order_user_address',function($join){
                            $join->on('order_info.order_no', '=', 'order_user_address.order_no');
                        }, null,null,'inner')
                        ->join('order_info_visit',function($join){
                            $join->on('order_info.order_no', '=', 'order_info_visit.order_no');
                        }, null,null,'left')
                        ->join('order_delivery',function($join){
                            $join->on('order_info.order_no', '=', 'order_delivery.order_no');
                        }, null,null,'left')
                        ->when(!empty($whereInArray),function($join) use ($whereInArray) {
                            return $join->whereIn('order_info.channel_id', $whereInArray);
                        })
                        ->when(!empty($whereArray),function($join) use ($whereArray) {
                            return $join->where($whereArray);
                        })
                        ->when(!empty($isUncontact),function($join) {
                            return $join->where(function ($join) {  //闭包返回的条件会包含在括号中
                                $join-> whereNull('order_info_visit.visit_id')
                                    ->orWhere([
                                        ['order_info_visit.visit_id', '0']
                                    ]);
                            });
                        })
                        ->orderBy('order_info.create_time', 'DESC')
                        ->skip(($page - 1) * $pagesize)->take($pagesize)
                        ->get();
                    $orderArray = objectToArray($orderList);
                    if ($orderArray) {
                        $orderIds = array_column($orderArray,"order_no");
                        $orderList =  DB::table('order_info as o')
                            ->select('o.create_time','o.order_no','o.order_amount','o.order_yajin','o.order_insurance','o.create_time','o.order_status','o.freeze_type','o.appid','o.pay_type','o.zuqi_type','o.user_id','o.mobile','o.predict_delivery_time','o.risk_check','d.address_info','d.name','d.consignee_mobile','v.visit_id','v.visit_text','v.id','l.logistics_no','c.matching')
                            ->distinct('o.order_no')
                            ->whereIn('o.order_no', $orderIds)
                            ->join('order_user_address as d',function($join){
                                $join->on('o.order_no', '=', 'd.order_no');
                            }, null,null,'inner')
                            ->join('order_info_visit as v',function($join){
                                $join->on('o.order_no', '=', 'v.order_no');
                            }, null,null,'left')
                            ->join('order_delivery as l',function($join){
                                $join->on('o.order_no', '=', 'l.order_no');
                            }, null,null,'left')
                            ->join('order_user_certified as c',function($join){
                                $join->on('o.order_no', '=', 'c.order_no');
                            }, null,null,'left')
                            ->orderBy('o.create_time', 'DESC')
                            ->get();
                        $orderArrays['data'] = array_column(objectToArray($orderList),NULL,'order_no');;
                        $orderArrays['orderIds'] = $orderIds;
                        $orderArrays['total'] = $count;
                        $orderArrays['is_out_channel'] = !empty($whereInArray)? true:false;
                        $orderArrays['last_page'] = ceil($count/$pagesize);


                    } else {
                        return false;
                    }


        }else {

            $orderArrays['total'] = $count;

        }
        return $orderArrays;


    }







    /**
     *  获取后台导出订单列表接口
     *  heaven
     * ->paginate: 参数
     *  perPage:表示每页显示的条目数量
    columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
    pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
    page:表示查询第几页及查询页码
     * @param array $param  获取订单列表参数
     */
    public static function getAdminExportOrderList($param = array(), $pagesize=5)
    {
        $whereArray = array();
        $orWhereArray = array();
        $isUncontact = 0;
//        $visitWhere = array();
        //根据用户id
        if (isset($param['user_id']) && !empty($param['user_id'])) {

            $whereArray[] = ['o.user_id', '=', $param['user_id']];
        }
        //根据订单编号
        if (isset($param['order_no']) && !empty($param['order_no'])) {

            $whereArray[] = ['o.order_no', '=', $param['order_no']];
        }

        //根据手机号
        if (isset($param['kw_type']) && $param['kw_type']=='mobile' && !empty($param['keywords']))
        {
            $whereArray[] = ['o.mobile', '=', $param['keywords']];
        }
        //根据订单号
        elseif (isset($param['kw_type']) && $param['kw_type']=='order_no' && !empty($param['keywords']))
        {
            $whereArray[] = ['o.order_no', '=', $param['keywords']];
        }

        if (isset($param['mobile']) && !empty($param['mobile'])) {
            $whereArray[] = ['d.consignee_mobile', '=', $param['keywords']];
        }

        //应用渠道
        if (isset($param['order_appid']) && !empty($param['order_appid'])) {
            $whereArray[] = ['o.channel_id', '=', $param['order_appid']];
        }

        //支付类型
        if (isset($param['pay_type']) && !empty($param['pay_type'])) {
            $whereArray[] = ['o.pay_type', '=', $param['pay_type']];
        }

        //长短租类型
        if (isset($param['zuqi_type'])) {
            $whereArray[] = ['o.zuqi_type', '=', $param['zuqi_type']];
        }
        //订单状态
        if (isset($param['order_status']) && !empty($param['order_status'])) {
            if ($param['order_status'] == OrderStatus::validOrder) {
//                $whereInArray = [OrderStatus::OrderInService, OrderStatus::OrderDeliveryed,OrderStatus::OrderInStock, OrderStatus::OrderPayed, OrderStatus::OrderCompleted];

                $whereArray[] = ['o.order_status', '>=', OrderStatus::OrderPayed];
                $whereArray[] = ['o.order_status', '<=', OrderStatus::OrderCompleted];
                $whereArray[] = ['o.order_status', '!=', OrderStatus::OrderCancel];
                $whereArray[] = ['o.order_status', '!=', OrderStatus::OrderClosedRefunded];

            } else {

                $whereArray[] = ['o.order_status', '=', $param['order_status']];
            }
        }

        //下单时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && (!isset($param['end_time']) || empty($param['end_time']))) {
            $whereArray[] = ['o.create_time', '>=', strtotime($param['begin_time'])];
        }

        //下单时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['o.create_time', '>=', strtotime($param['begin_time'])];
            $whereArray[] = ['o.create_time', '<', (strtotime($param['end_time'])+3600*24)];
        }

        if (isset($param['visit_id'])) {
            if (empty($param['visit_id'])) {
                $isUncontact = 1;
            } else {

                $whereArray[] = ['v.visit_id', '=', $param['visit_id']];
            }
        }


        if (isset($param['page'])) {
            $page = $param['page'];
        } else {

            $page = 1;
        }
        $orderArrays = array();
        $orderList =  DB::connection("mysql_read")->table('order_info as o')
            ->select('o.order_no','o.order_amount','o.order_amount','o.goods_yajin','o.order_yajin','o.order_insurance','o.create_time','o.order_status','o.freeze_type','o.appid','o.pay_type','o.zuqi_type','o.user_id','o.mobile','o.predict_delivery_time','d.address_info','d.name','d.consignee_mobile','v.visit_id','v.visit_text','v.id','l.logistics_no','c.matching','c.cret_no')
            ->join('order_user_address as d',function($join){
                $join->on('o.order_no', '=', 'd.order_no');
            }, null,null,'inner')
            ->join('order_info_visit as v',function($join){
                $join->on('o.order_no', '=', 'v.order_no');
            }, null,null,'left')
            ->join('order_delivery as l',function($join){
                $join->on('o.order_no', '=', 'l.order_no');
            }, null,null,'left')
            ->join('order_user_certified as c',function($join){
                $join->on('o.order_no', '=', 'c.order_no');
            }, null,null,'left')
            ->where($whereArray)
            ->when(!empty($isUncontact),function($join) {
                return $join->where(function ($join) {  //闭包返回的条件会包含在括号中
                    $join-> whereNull('v.visit_id')
                        ->orWhere([
                            ['v.visit_id', '0']
                        ]);
                });
            })
            ->orderBy('o.create_time', 'DESC')
            ->skip(($page - 1) * $pagesize)->take($pagesize)
            ->get();

        $orderArrays = array_column(objectToArray($orderList),NULL,'order_no');

        return $orderArrays;

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
    public static function getUserNewOrder($user_id, $app_id){
        if(empty($user_id)){
            return false;
        }
        if(empty($app_id)){
            return false;
        }
        $where[]=['user_id','=',$user_id];
        $where[]=['appid','=',$app_id];
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
        //添加数据字段（租金 碎屏险+总租金）
        $orderArr['pay_amount'] = normalizeNum($orderArr['order_insurance'] + $orderArr['order_amount']);
        //修改数据格式
        $data = [
            'orderArr'=>$orderArr,
            'goodsArr'=>$goodsArr,
        ];
        return $data;

    }
}