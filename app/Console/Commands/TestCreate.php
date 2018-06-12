<?php

namespace App\Console\Commands;

use App\Lib\Channel\Channel;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:daoru';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try{
            DB::beginTransaction();
            $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->select('*')->where(['business_key'=>1])->limit(5)->get();
            $orders=objectToArray($datas01);

            foreach ($orders as $k=>$v){
                $order_type =1; //订单类型
                if($v['appid'] == 36 || $v['appid'] == 90 || $v['appid'] == 91 || $v['appid'] == 92){
                    $order_type =3;
                }

                $ChannelInfo = Channel::getChannel($v['appid']);
                if (!is_array($ChannelInfo)) {
                     DB::rollBack();
                     echo "获取渠道失败：".$v['order_no'];die;
                }
                $channel_id =intval($ChannelInfo['_channel']['id']);
                //获取商品
                $goods = \DB::connection('mysql_01')->table('zuji_order2_goods')->select('*')->where(['order_id'=>$v['order_id']])->first();
                $goods_info=objectToArray($goods);

                //获取状态
                $status =$this->getStatus($v['status']);
                //完成时间
                $complete_time =0;

                //获取发货信息
                $delivery = \DB::connection('mysql_01')->table('zuji_order2_delivery')->select('*')->where(['order_no'=>$v['order_no'],'business_key'=>1])->first();
                $delivery_info =objectToArray($delivery);
                $delivery_remark ="";
                $delivery_time =0;
                $confirm_time =0;
                $receive_time =0;
                if($delivery_info){
                    $delivery_remark =$delivery_info['delivery_remark'];
                    $delivery_time =$delivery_info['delivery_time'];
                    $confirm_time =$delivery_info['create_time'];
                    $receive_time =$delivery_info['confirm_time'];
                }
                echo 1;die;


                var_dump($delivery_info);die;
                $orderData =[
                    'order_no'=>$v['order_no'], //订单编号
                    'mobile'=>$v['mobile'],   //用户手机号
                    'user_id'=>$v['user_id'],  //订单类型
                    'order_type'=>$order_type, //订单类型 1线上订单2门店订单 3小程序订单
                    'order_status'=>$status['order_status'],//
                    'freeze_type'=>$status['freeze_type'],//
                    'pay_type'=>$v['payment_type_id'],//
                    'zuqi_type'=>$v['zuqi_type'],//
                    'remark'=>$delivery_remark,//
                    'order_amount'=>($v['amount']-$goods_info['yajin']-$v['yiwaixian'])/100,//订单实际总租金
                    'goods_yajin'=>($goods_info['yajin']+$goods_info['mianyajin'])/100,//商品总押金金额
                    'discount_amount'=>0,//商品优惠总金额
                    'order_yajin'=>$goods_info['yajin']/100,//实付商品总押金金额
                    'order_insurance'=>$v['yiwaixian']/100,//意外险总金额
                    'coupon_amount'=>$v['discount_amount'],//优惠总金额
                    'create_time'=>$v['create_time'],//
                    'update_time'=>$v['update_time'],//
                    'pay_time'=>$v['payment_time'],//
                    'confirm_time'=>$confirm_time,//
                    'delivery_time'=>$delivery_time,//
                    'appid'=>$v['appid'],//
                    'channel_id'=>$channel_id,//
                    'receive_time'=>$receive_time,//
                    'complete_time'=>$complete_time,//
                ];
                $res =Order::create($orderData);
                if(!$res->getQueueableId()){
                    DB::rollBack();
                    echo "订单导入失败:".$v['order_no'];die;
                }

                $goodsData =[
                    'order_no'=>$v['order_no'],
                    'goods_name'=>$v['goods_name'],
                    'zuji_goods_id'=>$v['goods_id'],
                    'zuji_goods_sn'=>$v['order_no'],
                    'goods_no'=>createNo(6),
                    'goods_thumb'=>$v['order_no'],
                    'prod_id'=>$v['order_no'],
                    'prod_no'=>$v['order_no'],
                    'brand_id'=>$v['order_no'],
                    'category_id'=>$v['order_no'],
                    'machine_id'=>$v['order_no'],
                    'user_id'=>$v['order_no'],
                    'quantity'=>$v['order_no'],
                    'goods_yajin'=>$v['order_no'],
                    'yajin'=>$v['order_no'],
                    'zuqi'=>$v['order_no'],
                    'zuqi_type'=>$v['order_no'],
                    'zujin'=>$v['order_no'],
                    'machine_value'=>$v['order_no'],
                    'chengse'=>$v['order_no'],
                    'discount_amount'=>$v['order_no'],
                    'coupon_amount'=>$v['order_no'],
                    'amount_after_discount'=>$v['order_no'],
                    'edition'=>$v['order_no'],
                    'business_key'=>$v['order_no'],
                    'business_no'=>$v['order_no'],
                    'market_price'=>$v['order_no'],
                    'price'=>$v['order_no'],
                    'specs'=>$v['order_no'],
                    'insurance'=>$v['order_no'],
                    'buyout_price'=>$v['order_no'],
                    'begin_time'=>$v['order_no'],
                    'end_time'=>$v['order_no'],
                    'weight'=>$v['order_no'],
                    'goods_status'=>$status['goods_status'],
                    'create_time'=>$v['order_no'],
                    'update_time'=>$v['order_no'],
                ];
                $res =OrderGoods::create($goodsData);
                if(!$res->getQueueableId()){
                    DB::rollBack();
                    echo "商品导入失败:".$v['order_no'];die;
                }


            }
            DB::commit();
            echo "导入成功";die;
        }catch (\Exception $e){
            DB::rollBack();
            echo $e->getMessage();
            die;
        }

    }

    public static function list($limit, $page=1)
    {
        return KnightInfo::paginate($limit, ['*'], 'page', $page);
    }
    //订单状态转换
    public function getStatus($status,$order_info){
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
                $array = ['order_status'=>6,'freeze_type'=>10,'goods_status'=>0,'complete_time'=>0];
                break;
            //已支付
            case 7:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //退款中
            case 9:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1,'complete_time'=>0];
                break;
            //已退款
            case 10:
                $array = [ 'order_status'=>8,'freeze_type'=>21,'goods_status'=>0,'complete_time'=>$update_time];
                break;
            //已发货
            case 11:
                $array = [ 'order_status'=>5,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //用户拒签
            case 12:
                $array = [ 'order_status'=>5,'freeze_type'=>20,'goods_status'=>1,'complete_time'=>0];
                break;
            //退货审核中
            case 13:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1,'complete_time'=>0];
                break;
            //退货中
            case 14:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1,'complete_time'=>0];
                break;
            //平台已收货
            case 15:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1,'complete_time'=>0];
                break;
            //检测合格
            case 16:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1,'complete_time'=>0];
                break;
            //检测不合格
            case 17:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1,'complete_time'=>0];
                break;
            //换货中
            case 18:
                $array = [ 'order_status'=>6,'freeze_type'=>30,'goods_status'=>4,'complete_time'=>0];
                break;
            //回寄中
            case 19:
                $array = [ 'order_status'=>6,'freeze_type'=>30,'goods_status'=>4,'complete_time'=>0];
                break;
            //买断中
            case 20:
                $array = [ 'order_status'=>6,'freeze_type'=>50,'goods_status'=>3,'complete_time'=>0];
                break;
            //已买断
            case 21:
                $array = [ 'order_status'=>9,'freeze_type'=>51,'goods_status'=>0,'complete_time'=>$order_info['update_time']];
                break;
            //资金已授权
            case 22:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //资金已解冻
            case 23:
                $array = [ 'order_status'=>8,'freeze_type'=>21,'goods_status'=>0,'complete_time'=>$update_time];
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
