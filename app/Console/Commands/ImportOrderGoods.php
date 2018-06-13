<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportOrderGoods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderGoods';

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
            $limit = 1;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $datas01 = $this->conn->table('zuji_order2')->select('*')->where(['business_key'=>1])->forPage($page,$limit)->get();
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
                    $goods_info =$this->getOrderGoods($v['order_no']);
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
                        $arr[$v['order_no']] =$orderData;
                    }
                }
                $page++;
                sleep(1000);
            } while ($page <= $totalpage);
            if(count($arr)>0){
                LogApi::notify("订单风控信息导入失败",$arr);
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }


    }








}
