<?php
/**
 *
 * 订单结算数据处理
 */
namespace App\Order\Modules\Repository;
use App\Lib\ApiStatus;
use App\Order\Models\OrderClearing;

class OrderRepository
{


    /**
     * 结算数据录入
     * @param $param
     * @return bool|string
     */
    public function create($param){

//        var_dump('创建订单...');
        //     var_dump($schema);
//        var_dump('创建订单结束...');die;
        $orderClearData = new OrderClearing();
        $orderClearData->order_no = $param['order_no'];
        $orderClearData->business_type = $param['business_type'];
        $orderClearData->business_no = $param['business_no'];
        $orderClearData->claim_name = isset($param['claim_name']) && !empty($param['claim_name'])?$param['claim_name']:'';
        $orderClearData->order_no = isset($param['claim_name']) && !empty($param['claim_name'])?$param['claim_name']:'';
        $orderClearData->order_no = isset($param['claim_name']) && !empty($param['claim_name'])?$param['claim_name']:'';
        $orderClearData->order_no = isset($param['claim_name']) && !empty($param['claim_name'])?$param['claim_name']:'';
        $orderClearData->order_no = isset($param['claim_name']) && !empty($param['claim_name'])?$param['claim_name']:'';
        $orderClearData->order_no = $param['order_no'];
        $orderClearData->order_no = $param['order_no'];
        $orderClearData->order_no = $param['order_no'];
        $orderClearData->order_no = $param['order_no'];
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
}