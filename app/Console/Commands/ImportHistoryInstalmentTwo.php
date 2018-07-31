<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class ImportHistoryInstalmentTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstalmentTwo';

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
        $_count1 = 0;
        $_count2 = 0;

        $appid =[
            1,2,3,4,7,8,9,11,12,13,14,15,16,18,21,22,28,
            40,41,42,43,44,45,46,47,48,49,
            50,51,52,53,54,55,56,57,58,59,
            60,61,62,63,64,65,66,67,68,69,
            70,71,72,73,74,75,76,77,78,79,
            80,81,82,83,84,85,86,87,88,89,
            93,94,95,96,97,98,122,123,131,132,
        ];

        $total = \App\Order\Models\Order::query()
            ->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 1000;
            $page   = 1;
            $totalpage = ceil($total/$limit);

            $arr =[];

            do {


                $res =  \App\Order\Models\Order::query()
                    ->select('order_no')
                    ->forPage($page,$limit)
                    ->orderBy('id', 'DESC')
                    ->get();

                $result = objectToArray($res);


                foreach($result as &$order) {

                    ++$_count1;
                    $bar->advance();

                    //查询分期数据  有记录则跳出
                    $instalmentInfo = \App\Order\Models\OrderGoodsInstalment::query()
                        ->where([
                            ['order_no', '=', $order['order_no']]
                        ])->count();
                    if($instalmentInfo>0){
                        continue;
                    }

                    // 旧系统 订单信息
                    $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_id', 'order_no', 'goods_id', 'user_id', 'zujin')->where(['order_no' => $order['order_no']])->first();
                    $orderInfo = objectToArray($orderInfo);
                    if(!$orderInfo){
                        continue;
                    }


                    // 分期数据
                    $instalment = \DB::connection('mysql_01')->table('zuji_order2_instalment')
                        ->where(['order_id' => $orderInfo['order_id']])
                        ->get();
                    $instalmentList = objectToArray($instalment);
                    if(empty($instalmentList)){
                        ++$_count2;
                        continue;
                    }

                    foreach($instalmentList as $item){

                        //$data['id']               = $item['id'];
                        $data['order_no']         = !empty($orderInfo['order_no']) ? $orderInfo['order_no'] : 0;
                        $data['goods_no']         = !empty($orderInfo['goods_id']) ? $orderInfo['goods_id'] : 0;
                        $data['user_id']          = !empty($orderInfo['user_id']) ? $orderInfo['user_id'] : 0;

                        $data['term']             = !empty($item['term']) ? $item['term'] : 0;
                        $data['times']            = !empty($item['times']) ? $item['times'] : 0;
                        $data['discount_amount']  = !empty($item['discount_amount']) ? $item['discount_amount'] : '0.00';
                        $data['status']           = !empty($item['status']) ? $item['status'] : 0;
                        $data['payment_time']     = !empty($item['payment_time']) ? $item['payment_time'] : 0;
                        $data['update_time']      = !empty($item['update_time']) ? $item['update_time'] : 0;
                        $data['remark']           = !empty($item['remark']) ? $item['remark'] : "";
                        $data['fail_num']         = !empty($item['fail_num']) ? $item['fail_num'] : 0;
                        $data['unfreeze_status']  = !empty($item['unfreeze_status']) ? $item['unfreeze_status'] : 0;


                        $data['day']              = 15;
                        $data['original_amount']  = !empty($orderInfo['zujin']) ? $orderInfo['zujin'] / 100 : '0.00';
                        $data['amount']           = !empty($item['amount']) ? $item['amount'] / 100 : '0.00';


                        // 插入数据
                        $ret = \App\Order\Models\OrderGoodsInstalment::insert($data);
                        if(!$ret){
                            $arr[$item['id']] = $item;
                        }

                    }


                }

                ++$page;

            } while ($page <= $totalpage);

            if(count($arr)>0){
                LogApi::notify("订单分期数据导入失败",$arr);
            }
            $bar->finish();
            LogApi::info("分期数据导入",[$_count1,$_count2]);
            echo "导入成功（{$_count1},{$_count2}）";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
