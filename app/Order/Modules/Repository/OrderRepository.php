<?php
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Lib\Common\SmsApi;
use App\Order\Models\Order;
use App\Order\Models\OrderGoodExtend;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderUserInfo;
use App\Order\Models\OrderYidun;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderInstalment;
use Illuminate\Support\Facades\DB;

class OrderRepository
{

    protected $order;
    protected $orderGoods;
    protected $orderUserInfo;
    protected $instalment;
    protected $yidun;
    protected $third;

    public function __construct(ThirdInterface $third,Order $order,OrderGoods $orderGoods,OrderUserInfo $orderUserInfo,OrderInstalment $instalment,OrderYidun $yidun)
    {
        $this->order = $order;
        $this->goods =$orderGoods;
        $this->user =$orderUserInfo;
        $this->instalment =$instalment;
        $this->yidun =$yidun;
        $this->third =$third;

    }
    public function create($data,$schema){

//        var_dump('创建订单...');
        //     var_dump($schema);
//        var_dump('创建订单结束...');die;
        $time =time();
        //用户信息
        $user_info = $schema['user_info'];
        //商品信息
        $sku_info = $schema['sku_info'];
        // 写入用户信息
        $user_data = [
            'order_no'=>$data['order_no'],
            'user_id' =>$user_info['address']['user_id'],
            'mobile' =>$user_info['address']['mobile'],
            'name'=>$user_info['address']['name'],
            'province_id'=>$user_info['address']['province_id'],
            'city_id'=>$user_info['address']['city_id'],
            'area_id'=>$user_info['address']['country_id'],
            'address_info'=>$user_info['address']['address'],
            'certified'=>$user_info['credit']['certified'],
            'cretified_platform'=>$user_info['credit']['certified_platform'],
            'credit'=>$user_info['credit']['credit'],
            'realname'=>$user_info['credit']['realname'],
            'cret_no'=>$user_info['credit']['cert_no'],
        ];
        $id =$this->user->insertGetId($user_data);
        if(!$id){
            return ApiStatus::CODE_30005;
        }
        $order_amount =0;
        $goods_amount =0;
        $goods_yajin =0;
        $coupon_amount =0;
        $coupon =[];
        $reduce_data=[];
        $goods_name ="";
        foreach ($sku_info as $k =>$v){
            $reduce_data[$k]['sku_id']=$v['sku']['sku_id'];
            $reduce_data[$k]['spu_id']=$v['sku']['spu_id'];
            $reduce_data[$k]['num']=$v['sku']['sku_num'];
            for ($i=0;$i<$v['sku']['sku_num'];$i++){
                $order_amount +=$v['sku']['amount'];
                $goods_amount +=$v['sku']['all_amount'];
                $goods_yajin  +=$v['sku']['yajin'];
                $coupon_amount+=$v['sku']['discount_amount'];
                if(isset($v['coupon']['coupon_no'])){
                    $coupon[]=$v['coupon']['coupon_no'];
                }

                $goods_name .=$v['sku']['spu_name']." ";
                // 保存 商品信息
                $goods_data = [
                    'goods_name'=>$v['sku']['spu_name'],
                    'goods_id'=>$v['sku']['sku_id'],
                    'goods_no'=>$v['sku']['sku_no']."-".++$i,
                    'prod_id'=>$v['sku']['spu_id'],
                    'prod_no'=>$v['sku']['spu_no'],
                    'brand_id'=>$v['sku']['brand_id'],
                    'category_id'=>$v['sku']['category_id'],
                    'user_id'=>$user_info['address']['user_id'],
                    'quantity'=>1,
                    'goods_yajin'=>$v['sku']['yajin'],
                    'yajin'=>$v['deposit']['yajin'],
                    'zuqi'=>$v['sku']['zuqi'],
                    'zuqi_type'=>$v['sku']['zuqi_type'],
                    'zujin'=>$v['sku']['zujin'],
                    'order_no'=>$data['order_no'],
                    'chengse'=>$v['sku']['chengse'],
                    'discount_amount'=>$v['sku']['discount_amount'],
                    'amount_after_discount'=>$v['sku']['amount'],
                    'edition'=>$v['sku']['edition'],
                    'market_price'=>$v['sku']['market_price'],
                    'price'=>$v['sku']['amount'],
                    'specs'=>json_encode($v['sku']['specs']),
                    'insurance'=>$v['sku']['yiwaixian'],
                    'buyout_price'=>$v['sku']['buyout_price'],
                    'weight'=>$v['sku']['weight'],
                ];
                $goods_id = $this->goods->insertGetId($goods_data);
                if(!$goods_id){
                    return ApiStatus::CODE_30005;
                }
                $v['sku']['goods_no']=$v['sku']['sku_no']."-".++$i;
                // 生成分期
                $instalment_data =array_merge($v,['order'=>$data],$user_info);
                //var_dump($instalment_data);die;
                $instalment = $this->instalment->create($instalment_data);
                if(!$instalment){
                    return ApiStatus::CODE_30005;
                }

            }
        }

        // 创建订单
        $order_data = [
            'order_status' => OrderStatus::OrderWaitPaying,
            'order_no' => $data['order_no'],  // 编号
            'user_id'=>$data['user_id'],
            'pay_type'=>$data['pay_type'],
            'goods_amount'=>$goods_amount,
            'order_amount'=>$order_amount,
            'credit'=>$user_info['credit']['credit'],
            'goods_yajin'=>$goods_yajin,
            'order_yajin'=>$goods_yajin,
            'coupon_amount'=>$coupon_amount,
            'appid'=>$data['appid'],
        ];
        $order_id =$this->order->insertGetId($order_data);
        if(!$order_id){
            return ApiStatus::CODE_30005;
        }
        //存储蚁盾信息
        $yidun_data =[
            'decision' => $user_info['yidun']['decision'],
            'order_no'=>$data['order_no'],  // 编号
            'score' => $user_info['yidun']['score'],
            'strategies' =>$user_info['yidun']['strategies'],
        ];
        $yidun_id =$this->yidun->insertGetId($yidun_data);
        if(!$yidun_id){
            return ApiStatus::CODE_30005;
        }


        // 如果有优惠券 使用优惠券接口 失败回滚
        // $this->third->UseCoupon();

        // 下单减少库存

       // $b =$this->third->ReduceStock($reduce_data);

        //创建订单后 发送支付短信。;
//            $b = SmsApi::sendMessage($user_info['user']['mobile'],'SMS_113450944',[
//                'goodsName' => $goods_name,    // 传递参数
//            ]);

        return true;
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
     * 根据订单id查询信息
     *
     */

    public static function getInfoById($orderNo){
            if (empty($orderNo)) return false;
            $order =  Order::query()->where([
                ['order_no', '=', $orderNo],
            ])->first();
            if (!$order) return false;
            return $order->toArray();
    }


    /**
     *
     * 根据订单id查询设备列表
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
     *
     * 根据订单号查询设备号信息
     *
     */

    public static function getGoodsExtendInfo($orderNo){
        if (empty($orderNo)) return false;
        $orderGoodExtendData =  OrderGoodExtend::query()->where([
            ['order_no', '=', $orderNo],
        ])->get();
        if (!$orderGoodExtendData) return false;
        return $orderGoodExtendData->toArray();
    }
    /**
     *
     * 查询订单是否可以支付
     *
     */
    public static function isPay($orderNo)
    {
        if (empty($orderNo)) return false;
        $orderData = Order::query()->where([
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
        return $orderData;

    }

    /**
     *
     * 查询未完成的订单
     *
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
     * 更新订单
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
        return $order->toArray();
        if (!$order) return false;
        $order->order_status = OrderStatus::OrderClosed;
        if ($order->save()) {
            return true;
        } else {

            return false;
        }

    }

    /**
     *  获取订单列表
     *
     * @param array $param  获取订单列表参数
     */
//    public static function getOrderList($param = array())
//    {
//            if (isset($param['userId']) && !empty($param['userId'])) {
//
//                Order::
//
//            }
//
//
//    }
    //获取订单信息
    public function get_order_info($where){
        $orderNo=$where['order_no'];
        $order =  Order::where([
            ['order_no', '=', $orderNo],
        ])->first();
        if (!$order){
            return false;
        }else{
            return $order;
        }
    }

    /**
     * @param array $param  orderNo 订单号
     * @return array|bool
     */
    public static function getOrderInfo($param = array())
    {
        if (empty($param)) {
            return false;
        }
        if (isset($param['orderNo']) && !empty($param['orderNo']))
        {

            $orderData = DB::table('order_info')
                ->leftJoin('order_userinfo', function ($join) {
                    $join->on('order_info.order_no', '=', 'order_userinfo.order_no');
                })
                ->where('order_info.order_no', '=', $param['orderNo'])
                ->select('order_info.*','order_userinfo.*')
                ->get();
            return $orderData->toArray();
        }

    }
    //更新订单状态
    public static function order_update($order_no){
        $data['freeze_type']='1';
        if(Order::where('order_no', '=', $order_no)->update($data)){
            return true;
        }else{
            return false;
        }
    }
    //更新订单状态
    public static function deny_update($order_no){
        $data['freeze_type']='0';
        if(Order::where('order_no', '=', $order_no)->update($data)){
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
        if (isset($param['begin_time']) && !empty($param['begin_time']) && empty($param['end_time'])) {
            $whereArray[] = ['order_info.create_time', '>=', $param['begin_time']];
        }

        //下单时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) && isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['order_info.create_time', '>=', $param['begin_time']];
            $whereArray[] = ['order_info.create_time', '<=', $param['end_time']];
        }

        if (isset($param['visit_id']) && !empty($param['visit_id']) ) {
            $whereArray[] = ['order_info_extend.visit_id', '<>', 0];
        }

//        sql_profiler();
        $orderList = DB::table('order_info')
            ->leftJoin('order_userinfo', 'order_info.order_no', '=', 'order_userinfo.order_no')
            ->leftJoin('order_info_extend','order_info.order_no', '=', 'order_info_extend.order_no')
            ->where($whereArray)
            ->select('order_info.*','order_userinfo.*')
            ->paginate($pagesize,$columns = ['*'], $pageName = '', $param['page']);
        return $orderList;

    }
}