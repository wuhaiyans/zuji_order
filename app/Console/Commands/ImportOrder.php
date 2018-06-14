<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use Illuminate\Console\Command;

class ImportOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    private $conn;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->conn =\DB::connection('mysql_01');

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $total = $this->conn->table('zuji_order2')->where(['business_key'=>1])->count();
        try{
            $limit = 10;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $datas01 = $this->conn->table('zuji_order2')->where(['business_key'=>1])->forPage($page,$limit)->get();
                $orders=objectToArray($datas01);
                foreach ($orders as $k=>$v){
                    //获取渠道
                    $channel_id =$this->getChannel($v['appid']);
                    //获取订单类型
                    $order_type =$this->getOrderType($v['appid']);
                    //获取状态
                    $status =$this->getStatus($v['status'],$v);
                    //获取发货信息
                    $delivery =$this->getOrderDelivery($v['order_no']);
                    //获取商品信息
                    $goods_info =$this->getOrderGoods($v['order_id']);

                    $orderData =[
                        'order_no'=>$v['order_no'], //订单编号
                        'mobile'=>$v['mobile'],   //用户手机号
                        'user_id'=>$v['user_id'],  //订单类型
                        'order_type'=>$order_type, //订单类型 1线上订单2门店订单 3小程序订单
                        'order_status'=>$status['order_status'],//
                        'freeze_type'=>$status['freeze_type'],//
                        'pay_type'=>$v['payment_type_id'],//
                        'zuqi_type'=>$v['zuqi_type'],//
                        'remark'=>$delivery['delivery_remark'],//
                        'order_amount'=>($v['amount']-$goods_info['yajin']-$v['yiwaixian'])/100,//订单实际总租金
                        'goods_yajin'=>($goods_info['yajin']+$goods_info['mianyajin'])/100,//商品总押金金额
                        'discount_amount'=>0,//商品优惠总金额
                        'order_yajin'=>$goods_info['yajin']/100,//实付商品总押金金额
                        'order_insurance'=>$v['yiwaixian']/100,//意外险总金额
                        'coupon_amount'=>$v['discount_amount'],//优惠总金额
                        'create_time'=>$v['create_time'],//
                        'update_time'=>$v['update_time'],//
                        'pay_time'=>$v['payment_time'],//
                        'confirm_time'=>$delivery['confirm_time'],//
                        'delivery_time'=>$delivery['delivery_time'],//
                        'appid'=>$v['appid'],//
                        'channel_id'=>$channel_id,//
                        'receive_time'=>$delivery['receive_time'],//
                        'complete_time'=>$status['complete_time'],//
                    ];
                    $res =Order::updateOrCreate($orderData);
                    if(!$res->getQueueableId()){
                        $arr['order'][$k] =$orderData;
                    }
                    //获取服务周期
                    //$service = $this->getOrderServiceTime($v['order_no']);
                    var_dump($v['order_no']);die;
                    //获取sku信息
                    $sku_info =$this->getSkuInfo($goods_info['sku_id']);
                    //获取spu信息
                    $spu_info =$this->getSpuInfo($goods_info['spu_id']);
                    //获取机型信息
                    $machine_name =$this->getSpuMachineInfo($spu_info['machine_id']);
                    $goodsData =[
                        'order_no'=>$v['order_no'],
                        'goods_name'=>$v['goods_name'],
                        'zuji_goods_id'=>$goods_info['sku_id'],
                        'zuji_goods_sn'=>$sku_info['sn'],
                        'goods_no'=>$goods_info['goods_id'],
                        'goods_thumb'=>$goods_info['thumb'],
                        'prod_id'=>$goods_info['spu_id'],
                        'prod_no'=>$spu_info['sn'],
                        'brand_id'=>$goods_info['brand_id'],
                        'category_id'=>$goods_info['category_id'],
                        'machine_id'=>$spu_info['machine_id'],
                        'user_id'=>$v['user_id'],
                        'quantity'=>1,
                        'goods_yajin'=>($goods_info['yajin']+$goods_info['mianyajin'])/100,
                        'yajin'=>$goods_info['yajin']/100,
                        'zuqi'=>$goods_info['zuqi'],
                        'zuqi_type'=>$goods_info['zuqi_type'],
                        'zujin'=>$goods_info['zujin']/100,
                        'machine_value'=>$machine_name,
                        'chengse'=>$goods_info['chengse'],
                        'discount_amount'=>0,
                        'coupon_amount'=>$v['discount_amount']/100,
                        'amount_after_discount'=>($goods_info['zuqi']*$goods_info['zujin']-$v['discount_amount'])/100,
                        'edition'=>$sku_info['edition'],
                        'business_key'=>0,
                        'business_no'=>'',
                        'market_price'=>$sku_info['market_price'],
                        'price'=>($goods_info['zuqi']*$goods_info['zujin']-$v['discount_amount']+$goods_info['yiwaixian']+$goods_info['yajin'])/100,
                        'specs'=>$goods_info['specs'],
                        'insurance'=>$goods_info['yiwaixian']/100,
                        'buyout_price'=>($sku_info['market_price']*120 -($goods_info['zuqi']*$goods_info['zujin']/100)),
                        'begin_time'=>$service['begin_time'],
                        'end_time'=>$service['end_time'],
                        'weight'=>$sku_info['weight'],
                        'goods_status'=>$status['goods_status'],
                        'create_time'=>$goods_info['create_time'],
                        'update_time'=>$goods_info['update_time'],
                    ];
                    $res =OrderGoods::updateOrCreate($goodsData);
                    if(!$res->getQueueableId()){
                        $arr['goods'][$k] =$orderData;
                    }
                }
                $page++;
                sleep(2);
            } while ($page <= $totalpage);
            if(count($arr)>0){
                LogApi::notify("订单风控信息导入失败",$arr);
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getLine();
            die;
        }
    }

    /**
     * 获取SPU
     * @param $spu_id
     * @return array
     */
    public function getSpuInfo($spu_id){

        $datas01 = $this->conn->table('zuji_goods_spu')->select('*')->where(['id'=>$spu_id])->first();
        return objectToArray($datas01);
    }
    /**
     * 获取SKU
     * @param $spu_id
     * @return array
     */
    public function getSkuInfo($sku_id){

        $datas01 = $this->conn->table('zuji_goods_sku')->select('*')->where(['sku_id'=>$sku_id])->first();
        return objectToArray($datas01);
    }
    /**
     * 获取机型信息
     * @param $spu_id
     * @return string
     */
    public function getSpuMachineInfo($machine_id){
        $datas01 = $this->conn->table('zuji_goods_machine_model')->select('*')->where(['id'=>$machine_id])->first();
        $machine_name="";
        if($datas01){
            $machine =objectToArray($datas01);
            $machine_name =$machine['name'];
        }
        return $machine_name;
    }




    /**
     * 获取订单服务周期
     * @param $order_no
     * @return mixed
     */
    public function getOrderServiceTime($order_no){
        $arr['begin_time'] =0;
        $arr['end_time'] =0;
        //订单服务周期
        $service =\DB::connection('mysql_01')->table("zuji_order2_service")->where(['order_no'=>$order_no])->get()->first();
        if($service){
            $serviceData=objectToArray($service);
            $arr['begin_time'] =$serviceData['begin_time'];
            $arr['end_time'] =$serviceData['end_time'];
        }
        return $arr;

    }

    public function getOrderGoods($order_id){
        //获取商品
        $goods = $this->conn->table('zuji_order2_goods')->select('*')->where(['order_id'=>$order_id])->first();
        return objectToArray($goods);
    }
    /**
     * 获取发货信息
     * @param $order_no
     * @return mixed
     */

    public function getOrderDelivery($order_no){
        $delivery = $this->conn->table('zuji_order2_delivery')->where(['order_no'=>$order_no])->first();
        $delivery_info =objectToArray($delivery);
        var_dump($delivery_info);die;
        $arr['delivery_remark'] ="";
        $arr['delivery_time'] =0;
        $arr['confirm_time'] =0;
        $arr['receive_time'] =0;
        if($delivery_info){
            $arr['delivery_remark'] =$delivery_info['delivery_remark'];
            $arr['delivery_time'] =$delivery_info['delivery_time'];
            $arr['confirm_time'] =$delivery_info['create_time'];
            $arr['receive_time'] =$delivery_info['confirm_time'];
        }
        return $arr;
    }

    /**
     * 获取订单类型
     * @param $appid
     */
    private function getOrderType($appid){
        $order_type =1; //订单类型
        if($appid == 36 || $appid == 90 || $appid == 91 || $appid == 92){
            $order_type =3;
        }
        return $order_type;
    }

    /**
     * 获取渠道ID
     * @param $appid
     * @return mixed
     */
    private function getChannel($appid){
        $channel =$this->conn->table('zuji_channel_appid')->select('*')->where(['id'=>$appid])->first();
        $ret=objectToArray($channel);
        return $ret['channel_id'];
    }
    //订单状态转换
    private function getStatus($status,$order_info){
        $array = [];
        $update_time =$order_info['update_time'];
        if($order_info['refund_time']!=0){
            $update_time =$order_info['refund_time'];
        }
        switch($status){
            //已下单
            case 1:
                $array = ['order_status'=>1, 'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //已取消
            case 2:
                $array = ['order_status'=>7,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>$order_info['update_time']];
                break;
            //订单关闭
            case 3:
                $array = ['order_status'=>8,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>$order_info['update_time']];
                break;
            //租用中
            case 4:
                $array = ['order_status'=>6,'freeze_type'=>0,'goods_status'=>10,'complete_time'=>0];
                break;
            //已支付
            case 7:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //退款中
            case 9:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //已退款
            case 10:
                $array = [ 'order_status'=>8,'freeze_type'=>0,'goods_status'=>21,'complete_time'=>$update_time];
                break;
            //已发货
            case 11:
                $array = [ 'order_status'=>5,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //用户拒签
            case 12:
                $array = [ 'order_status'=>5,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //退货审核中
            case 13:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //退货中
            case 14:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //平台已收货
            case 15:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //检测合格
            case 16:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //检测不合格
            case 17:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //换货中
            case 18:
                $array = [ 'order_status'=>6,'freeze_type'=>4,'goods_status'=>30,'complete_time'=>0];
                break;
            //回寄中
            case 19:
                $array = [ 'order_status'=>6,'freeze_type'=>4,'goods_status'=>30,'complete_time'=>0];
                break;
            //买断中
            case 20:
                $array = [ 'order_status'=>6,'freeze_type'=>3,'goods_status'=>50,'complete_time'=>0];
                break;
            //已买断
            case 21:
                $array = [ 'order_status'=>9,'freeze_type'=>0,'goods_status'=>51,'complete_time'=>$order_info['update_time']];
                break;
            //资金已授权
            case 22:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //资金已解冻
            case 23:
                $array = [ 'order_status'=>8,'freeze_type'=>0,'goods_status'=>21,'complete_time'=>$update_time];
                break;
            //用户归还
            case 25:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //已完成
            case 26:
                $array = [ 'order_status'=>9,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>$order_info['update_time']];
                break;
        }
        return $array;
    }


}
