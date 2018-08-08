<?php

namespace App\Console\Commands;

//use App\Lib\Common\LogApi;
use App\Lib\Common\LogApi;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderCreater;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ImportUpdateOrderGoods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportUpdateOrderGoods';

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
        $appid =[
            1,2,3,4,7,8,9,11,12,13,14,15,16,18,21,22,28,
            40,41,42,43,44,45,46,47,48,49,
            50,51,52,53,54,55,56,57,58,59,
            60,61,62,63,64,65,66,67,68,69,
            70,71,72,73,74,75,76,77,78,79,
            80,81,82,83,84,85,86,87,88,89,
            93,94,95,96,97,98,122,123,131,132,
        ];


        $total = \DB::connection('mysql_01')->table('zuji_order2')->whereIn("appid",$appid)
           ->count();
        $bar = $this->output->createProgressBar($total);
        try{

            $limit = 500;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            $orderId =0;
            do {

                $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->whereIn("appid",$appid)->forPage($page,$limit)->get();
                $orders=objectToArray($datas01);
                foreach ($orders as $k=>$v){

                    $goods_info =$this->getOrderGoods($v['order_id']);
                    $sku_info =$this->getSkuInfo($goods_info['sku_id']);
                    $goodsData =[
                        'goods_yajin'=>($v['yajin']+$v['mianyajin'])/100,   //全部改成 读取 订单表的
                        'yajin'=>$v['yajin']/100,//全部改成 读取 订单表的
                        'zuqi'=>$v['zuqi'],//全部改成 读取 订单表的
                        'zuqi_type'=>$v['zuqi_type'],//全部改成 读取 订单表的
                        'zujin'=>$v['zujin']/100,//全部改成 读取 订单表的
                        'coupon_amount'=>$v['discount_amount']/100,//全部改成 读取 订单表的
                        'amount_after_discount'=>($v['zuqi']*$v['zujin']-$v['discount_amount'])/100<0 ?0:($v['zuqi']*$v['zujin']-$v['discount_amount'])/100,//全部改成
                        'price'=>($v['zuqi']*$v['zujin']-$v['discount_amount']+$v['yiwaixian']+$v['yajin'])/100 <0?0:($v['zuqi']*$v['zujin']-$v['discount_amount']+$v['yiwaixian']+$v['yajin'])/100 ,
                        'insurance'=>$v['yiwaixian']/100,
                        'buyout_price'=>($sku_info['market_price']*1.2 -($v['zuqi']*$v['zujin']/100))<0?0:($sku_info['market_price']*1.2 -($v['zuqi']*$v['zujin']/100)),
                    ];

                    $res =OrderGoods::where([
                        ['order_no', '=', $v['order_no']],
                    ])->update($goodsData);

                    if($goods_info['zujin'] !=$v['zujin']){
                        $arr['goods_unequal'][$k] = [['order_no'=>$v['order_no']],$goodsData];
                    }
                    if(!$res){
                        $arr['goods_error'][$k] =[['order_no'=>$v['order_no']],$goodsData];
                    }
                    $bar->advance();
                }
                ++$page;

            } while ($page <= $totalpage);
            $bar->finish();
            LogApi::info("order_import_record_goods_unequal:",$arr['goods_unequal']);
            LogApi::info("order_import_record_goods_error:",$arr['goods_error']);
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }
    /**
     * 判断订单是否可以导入
     * @param $order_no
     * @return bool
     */
    public static function isAllowImport($order_no){
        $appid =[
            1,2,3,4,7,8,9,11,12,13,14,15,16,18,21,22,28,
            40,41,42,43,44,45,46,47,48,49,
            50,51,52,53,54,55,56,57,58,59,
            60,61,62,63,64,65,66,67,68,69,
            70,71,72,73,74,75,76,77,78,79,
            80,81,82,83,84,85,86,87,88,89,
            93,94,95,96,97,98,122,123,131,132,
        ];
        $total = \DB::connection('mysql_01')->table('zuji_order2')->where(array(['order_no','=',$order_no]))->whereIn("appid",$appid)
            ->count();
        if($total >0){
            return true;
        }
        return false;

    }

    /**
     * 获取follow
     * @param $order_id
     * @param $new_status
     * @return array
     */
    public function getOrderFollow($order_id,$new_status){

        $datas01 = $this->conn->table('zuji_order2_follow')->select('*')->where(['order_id'=>$order_id,'new_status'=>$new_status])->first();
        $arr=[];
        if($datas01){
            $arr =objectToArray($datas01);
        }
        return $arr;
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
        $arr['delivery_remark'] ="";
        $arr['delivery_time'] =0;
        $arr['confirm_time'] =0;
        $arr['receive_time'] =0;

        if($delivery){
            $delivery_info =objectToArray($delivery);
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
                $array = ['order_status'=>8,'freeze_type'=>0,'goods_status'=>71,'complete_time'=>$order_info['update_time']];
                break;
            //租用中
            case 4:
                $array = ['order_status'=>6,'freeze_type'=>0,'goods_status'=>10,'complete_time'=>0];
                break;
            //已支付
            case 7:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //确认订单
            case 8:
                $array = [ 'order_status'=>4,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //退款中
            case 9:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>20,'complete_time'=>0];
                break;
            //已退款
            case 10:
                $array = [ 'order_status'=>8,'freeze_type'=>0,'goods_status'=>71,'complete_time'=>$update_time];
                break;
            //已发货
            case 11:
                $array = [ 'order_status'=>5,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //用户拒签
            case 12:
                $array = [ 'order_status'=>5,'freeze_type'=>0,'goods_status'=>20,'complete_time'=>0];
                break;
            //退货审核中
            case 13:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>20,'complete_time'=>0];
                break;
            //退货中
            case 14:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>20,'complete_time'=>0];
                break;
            //平台已收货
            case 15:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>20,'complete_time'=>0];
                break;
            //检测合格
            case 16:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>20,'complete_time'=>0];
                break;
            //检测不合格
            case 17:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>20,'complete_time'=>0];
                break;
            //换货中
            case 18:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>30,'complete_time'=>0];
                break;
            //回寄中
            case 19:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>30,'complete_time'=>0];
                break;
            //买断中
            case 20:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>50,'complete_time'=>0];
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
                $array = [ 'order_status'=>8,'freeze_type'=>0,'goods_status'=>71,'complete_time'=>$update_time];
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
