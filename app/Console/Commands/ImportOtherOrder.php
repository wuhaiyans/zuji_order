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

class ImportOtherOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOtherOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    private $conn;
    private $conn2;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->conn =\DB::connection('mysql_01');

        $this->conn2 =\DB::connection('mysql_02');

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

        $whereArr[] =['business_key','<>','10'];
        $whereArr[] =['order_id','>','32128'];

        //3点之前非关闭的订单，3点之后所有订单
        $total = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)->whereNotIn("appid",$appid)
           ->count();
        $bar = $this->output->createProgressBar($total);
        try{

            $limit = 500;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            $orderId =0;
            do {

                $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)->whereNotIn("appid",$appid)->forPage($page,$limit)->get();
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
                    //获取支付时间
                    $payment_time = intval($v['payment_time']);
                    $follow_info =$this->getOrderFollow($v['order_id'],7);
                    if(!empty($follow_info)){
                        $payment_time =$follow_info['create_time'];
                    }
                    $follow_info =$this->getOrderFollow($v['order_id'],22);
                    if(!empty($follow_info)){
                        $payment_time =$follow_info['create_time'];
                    }
                    //关闭已退款
                    $follow_info =$this->getOrderFollow($v['order_id'],10);
                    if(!empty($follow_info)){
                        $status['order_status'] =8;
                    }
                    $follow_info =$this->getOrderFollow($v['order_id'],23);
                    if(!empty($follow_info)){
                        $status['order_status'] =8;
                    }

                    //判断时间 大于2018-7-26 19:00:00 以后的下单 要根据手机号重新查询user_id

                    if(intval($v['create_time']) >= 1532563200){
                        $userInfo =$this->getOrderUserId($v['mobile']);
                        if(empty($userInfo)){
                            echo "用户信息未找到：".$v['mobile'];die;
                        }
                        $v['user_id'] = $userInfo['id'];
                    }

                    //查询订单是否存在
                    $where=[];
                    $where[]=['order_no','=',$v['order_no']];
                    $order = Order::where($where)->first();
                    if($order){
                        continue;
                    }

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
                        'order_amount'=>($v['zujin']*$v['zuqi']-$v['discount_amount'])/100 <0?0:($v['zujin']*$v['zuqi']-$v['discount_amount'])/100 ,//订单实际总租金
                        'goods_yajin'=>($v['yajin']+$v['mianyajin'])/100,//商品总押金金额
                        'discount_amount'=>0,//商品优惠总金额
                        'order_yajin'=>$v['yajin']/100,//实付商品总押金金额
                        'order_insurance'=>$v['yiwaixian']/100,//意外险总金额
                        'coupon_amount'=>$v['discount_amount']/100,//优惠总金额
                        'create_time'=>intval($v['create_time']),//
                        'update_time'=>intval($v['update_time']),//
                        'pay_time'=>intval($v['payment_time']),//
                        'confirm_time'=>intval($delivery['confirm_time']),//
                        'delivery_time'=>intval($delivery['delivery_time']),//
                        'appid'=>$v['appid'],//
                        'channel_id'=>$channel_id,//
                        'receive_time'=>intval($delivery['receive_time']),//
                        'complete_time'=>intval($status['complete_time']),//
                    ];
                    $res =Order::insert($orderData);
                    if(!$res){
                        $arr['order'][$k] =$v['order_no'];
                    }
                    //获取服务周期
                    $service = $this->getOrderServiceTime($v['order_no']);


                    //商品信息查询 如果是 2018-7-26 19:00:00 以后的下单 要根据新的查询
                    if(intval($v['create_time']) >= 1532563200){
                        //获取sku信息
                        $sku_info =$this->getSkuInfos($goods_info['sku_id']);
                        //获取spu信息
                        $spu_info =$this->getSpuInfos($goods_info['spu_id']);
                        if(empty($sku_info)){
                            //获取sku信息
                            $sku_info =$this->getSkuInfo($goods_info['sku_id']);
                        }
                        if(empty($spu_info)){
                            $spu_info =$this->getSpuInfo($goods_info['spu_id']);
                        }
                    }
                    //商品信息查询 如果是 2018-7-26 19:00:00 以前的保持不变
                    else{
                        //获取sku信息
                        $sku_info =$this->getSkuInfo($goods_info['sku_id']);
                        //获取spu信息
                        $spu_info =$this->getSpuInfo($goods_info['spu_id']);
                    }


                    //获取机型信息
                    $machine_name =$this->getSpuMachineInfo($spu_info['machine_id']);
                    $goodsData =[
                        'order_no'=>$v['order_no'],
                        'goods_name'=>$v['goods_name'],
                        'zuji_goods_id'=>$sku_info['sku_id'],
                        'zuji_goods_sn'=>$sku_info['sn'],
                        'goods_no'=>$goods_info['goods_id'],
                        'goods_thumb'=>$spu_info['thumb'],
                        'prod_id'=>$spu_info['id'],
                        'prod_no'=>$spu_info['sn'],
                        'brand_id'=>$goods_info['brand_id'],
                        'category_id'=>$goods_info['category_id'],
                        'machine_id'=>$spu_info['machine_id'],
                        'user_id'=>$v['user_id'],
                        'quantity'=>1,
                        'goods_yajin'=>($v['yajin']+$v['mianyajin'])/100,
                        'yajin'=>$v['yajin']/100,
                        'zuqi'=>$v['zuqi'],
                        'zuqi_type'=>$v['zuqi_type'],
                        'zujin'=>$v['zujin']/100,
                        'machine_value'=>$machine_name,
                        'chengse'=>$goods_info['chengse'],
                        'discount_amount'=>0,
                        'coupon_amount'=>$v['discount_amount']/100,
                        'amount_after_discount'=>($v['zuqi']*$v['zujin']-$v['discount_amount'])/100<0 ?0:($v['zuqi']*$v['zujin']-$v['discount_amount'])/100,
                        'edition'=>$sku_info['edition'],
                        'business_key'=>0,
                        'business_no'=>'',
                        'market_price'=>$sku_info['market_price'],
                        'price'=>($v['zuqi']*$v['zujin']-$v['discount_amount']+$v['yiwaixian']+$v['yajin'])/100 <0?0:($v['zuqi']*$v['zujin']-$v['discount_amount']+$v['yiwaixian']+$v['yajin'])/100 ,
                        'specs'=>$goods_info['specs'],
                        'insurance'=>$v['yiwaixian']/100,
                        'buyout_price'=>($sku_info['market_price']*1.2 -($v['zuqi']*$v['zujin']/100))<0?0:($sku_info['market_price']*1.2 -($v['zuqi']*$v['zujin']/100)),
                        'begin_time'=>$service['begin_time'],
                        'end_time'=>$service['end_time'],
                        'weight'=>$sku_info['weight'],
                        'goods_status'=>$status['goods_status'],
                        'create_time'=>intval($goods_info['create_time']),
                        'update_time'=>intval($goods_info['update_time']),
                    ];
                    $res =OrderGoods::insert($goodsData);
                    if(!$res){
                        $arr['goods'][$k] =$goodsData;
                    }
                    /**
                     * 判断订单状态 如果是已下单 的 创建支付单
                     */
                    /**
                     * 创建支付单
                     * @param array $param 创建支付单数组
                     * $param = [<br/>
                     *		'payType' => '',//支付方式 【必须】<br/>
                     *		'payChannelId' => '',//支付渠道 【必须】<br/>
                     *		'userId' => '',//业务用户ID 【必须】<br/>
                     *		'businessType' => '',//业务类型（租机业务 ）【必须】<br/>
                     *		'businessNo' => '',//业务编号（订单编号）【必须】<br/>
                     *		'paymentAmount' => '',//Price 支付金额（总租金），单位：元【必须】<br/>
                     *		'fundauthAmount' => '',//Price 预授权金额（押金），单位：元【必须】<br/>
                     *		'paymentFenqi' => '',//int 分期数，取值范围[0,3,6,12]，0：不分期【必须】<br/>
                     * ]<br/>
                     * @return mixed boolen：flase创建失败|array $result 结果数组
                     * $result = [<br/>
                     *		'isPay' => '',订单是否需要支付（true：需要支付；false：无需支付）【订单是否创建支付单】//<br/>
                     *		'withholdStatus' => '',是否需要签代扣（true：需要签约代扣；false：无需签约代扣）//<br/>
                     *		'paymentStatus' => '',是否需要支付（true：需要支付；false:无需支付）//<br/>
                     *		'fundauthStatus' => '',是否需要预授权（true：需要预授权；false：无需预授权）//<br/>
                     * ]
                     */
                    if($v['status'] ==1){
                        $fenqi =$goodsData['zuqi'];
                        if($goodsData['zuqi_type'] ==1){
                            $fenqi=0;
                        }
                        $payData =[
                            'payType' =>$v['payment_type_id'],//支付方式 【必须】<br/>
                            'payChannelId' => 2,//支付渠道 【必须】<br/>
                            'userId' =>$v['user_id'],//业务用户ID 【必须】<br/>
                            'businessType' =>OrderStatus::BUSINESS_ZUJI,//业务类型（租机业务 ）【必须】<br/>
                            'businessNo' => $v['order_no'],//业务编号（订单编号）【必须】<br/>
                            'orderNo' =>$v['order_no'],//业务编号（订单编号）【必须】<br/>
                            'paymentAmount' => $goodsData['amount_after_discount'],//Price 支付金额（总租金），单位：元【必须】<br/>
                            'fundauthAmount' => $goodsData['yajin'],//Price 预授权金额（押金），单位：元【必须】<br/>
                            'paymentFenqi' => $fenqi,//int 分期数，取值范围[0,3,6,12]，0：不分期【必须】<br/>
                        ];
                        $res =OrderCreater::createPay($payData);
                        if(!$res){
                            $arr['order_pay'][$k] =$payData;
                        }
                    }
                    $bar->advance();
                    $orderId =$v['order_id'];
                }
                ++$page;

            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                 LogApi::notify("导入订单数据失败",$arr);
            }
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

        $whereArr[] =['business_key','<>','10'];
        $whereArr[] =['order_no','=',$order_no];
        //3点之前非关闭的订单，3点之后所有订单
        $total = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)->whereNotIn("appid",$appid)
            ->count();
        if($total >0){
            return true;
        }
        return false;

    }
    /**
     * 查询需要导出的订单
     * @param $order_no
     * @return bool
     */
    public static function isOrderImport(){
        $appid =[
            1,2,3,4,7,8,9,11,12,13,14,15,16,18,21,22,28,
            40,41,42,43,44,45,46,47,48,49,
            50,51,52,53,54,55,56,57,58,59,
            60,61,62,63,64,65,66,67,68,69,
            70,71,72,73,74,75,76,77,78,79,
            80,81,82,83,84,85,86,87,88,89,
            93,94,95,96,97,98,122,123,131,132,
        ];

        $whereArr[] =['business_key','<>','10'];

        $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)->whereNotIn("appid",$appid)->get();
        $orders=objectToArray($datas01);
        return $orders;

    }
    /**
     * 获取用户信息
     * @param $mobile 用户手机号
     * @return array 用户信息
     */
    public function getOrderUserId($mobile){

        $datas01 = $this->conn2->table('zuji_member')->select('*')->where(['mobile'=>$mobile])->first();
        $arr=[];
        if($datas01){
            $arr =objectToArray($datas01);
        }
        return $arr;
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
     * 获取SPU
     * @param $spu_id
     * @return array
     */
    public function getSpuInfos($spu_id){

        $datas01 = $this->conn2->table('zuji_goods_spu')->select('*')->where(['spu_ids'=>$spu_id])->first();
        return objectToArray($datas01);
    }
    /**
     * 获取SKU
     * @param $spu_id
     * @return array
     */
    public function getSkuInfos($sku_id){

        $datas01 = $this->conn2->table('zuji_goods_sku')->select('*')->where(['sku_ids'=>$sku_id])->first();
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
