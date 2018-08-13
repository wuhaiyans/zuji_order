<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class ImportNewInstalment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:NewInstalment';

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
    public function handle(){

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

        //3点之前非关闭的订单，3点之后所有订单
        $total = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)->whereNotIn("appid",$appid)
            ->count();
        $bar = $this->output->createProgressBar($total);


        try{

            $limit  = 1000;
            $page   = 1;
            $totalpage = ceil($total/$limit);

            $arr =[];

            do {

                $orderList = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)
                    ->whereNotIn("appid",$appid)
                    ->orderby('order_id',"DESC")
                    ->forPage($page,$limit)
                    ->get();
                $orderList = objectToArray($orderList);

                foreach($orderList as $order) {
                    $NewOrder    = \App\Order\Models\Order::where(['order_no'=>$order['order_no']])->first();
                    $NewOrder = objectToArray($NewOrder);
                    if(!$NewOrder){
                        $arr[] = $order['order_id'];
                        continue;
                    }
                    $user_id = $NewOrder['user_id'];

                    //查询分期
                    $instalmentList = \DB::connection('mysql_01')->table('zuji_order2_instalment')
                        ->where([
                            'order_id'  => $order['order_id']
                        ])->get();
                    $instalmentList = objectToArray($instalmentList);
                    if(!$instalmentList){
                        $arr[] = $order['order_id'];
                        continue;
                    }

                    foreach($instalmentList as $instalment){

                        //$data['id']               = $instalment['id']; // ID重复
                        $data['order_no']         = $order['order_no'];
                        $data['goods_no']         = $order['goods_id'];
                        $data['user_id']          = $user_id;

                        $data['term']             = $instalment['term'];
                        $data['times']            = $instalment['times'];
                        $data['discount_amount']  = $instalment['discount_amount'];
                        $data['status']           = $instalment['status'];
                        $data['payment_time']     = $instalment['payment_time'];
                        $data['update_time']      = $instalment['update_time'];
                        $data['remark']           = $instalment['remark'];
                        $data['fail_num']         = $instalment['fail_num'];
                        $data['unfreeze_status']  = $instalment['unfreeze_status'];


                        $data['day']              = 15;
                        $data['original_amount']  = $order['zujin'] / 100;
                        $data['amount']           = $instalment['amount'] / 100;

                        //有记录则跳出
                        $info = \App\Order\Models\OrderGoodsInstalment::query()
                            ->where([
                                ['order_no', '=', $order['order_no']],
                                ['term', '=', $instalment['term']]
                            ])->first();
                        if($info){
                            continue;
                        }

                        // 插入数据
                        $ret = \App\Order\Models\OrderGoodsInstalment::insert($data);
                        if(!$ret){
                            $arr[] = $order['order_id'] . '_' . $instalment['id'];
                        }

                    }

                    $bar->advance();

                }

                ++$page;

            } while ($page <= $totalpage);

            if(count($arr)>0){
                LogApi::notify("订单分期数据导入失败",$arr);
            }
            $bar->finish();
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
