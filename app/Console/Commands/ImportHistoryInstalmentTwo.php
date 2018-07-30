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
        //$totalSql = 'select count(order_info.id) as num from order_info left join order_goods_instalment ON order_info.order_no=order_goods_instalment.order_no WHERE order_goods_instalment.id IS NULL';


        $total = \App\Order\Models\Order::query()
            ->where([
                ['order_goods_instalment.id', '=', null],
            ])
            ->leftJoin('order_goods_instalment', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
            ->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 2;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];

            while($page <= $totalpage) {

                // 未导入的订单id
                $arr = [];
                $res =  \App\Order\Models\Order::query()
                    ->where([
                        ['order_goods_instalment.id', '=', null],
                    ])
                    ->leftJoin('order_goods_instalment', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                    ->orderBy('order_info.id', 'ASC')
                    ->forPage($page,$limit)
                    ->get()->toArray();
                $result = objectToArray($res);


                foreach($result as &$item) {
                    ++$_count1;
                    $bar->advance();
                    // 旧系统 订单信息
                    $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_no', 'goods_id', 'user_id', 'zujin', "appid", "business_key")->where(['order_no' => $item['order_no']])->first();
                    $orderInfo = objectToArray($orderInfo);
                    if(!$orderInfo){
                        continue;
                    }

                    // 去除小程序分期
                    $isAllow = \App\Console\Commands\ImportOrder::isAllowImport($orderInfo['order_no']);
                    if(!$isAllow){
                        ++$_count2;
                        continue;
                    }

                    // 分期数据

                    $instalment = \DB::connection('mysql_01')->table('zuji_order2_instalment')
                        ->where(['order_no' => $item['order_no']])
                        ->get()->toArray();
                    $instalmentList = objectToArray($instalment);

                    foreach($instalmentList as $item){

                        $data['id']               = $item['id'];
                        $data['order_no']         = $orderInfo['order_no'];
                        $data['goods_no']         = $orderInfo['goods_id'];
                        $data['user_id']          = $orderInfo['user_id'];

                        $data['term']             = $item['term'];
                        $data['times']            = $item['times'];
                        $data['discount_amount']  = $item['discount_amount'];
                        $data['status']           = $item['status'];
                        $data['payment_time']     = $item['payment_time'];
                        $data['update_time']      = $item['update_time'];
                        $data['remark']           = $item['remark'];
                        $data['fail_num']         = $item['fail_num'];
                        $data['unfreeze_status']  = $item['unfreeze_status'];


                        $data['day']              = 15;
                        $data['original_amount']  = $orderInfo['zujin'] / 100;
                        $data['amount']           = $item['amount'] / 100;

                        //有记录则跳出
                        $info = \App\Order\Models\OrderGoodsInstalment::query()
                            ->where([
                                ['id', '=', $item['id']]
                            ])->first();
                        if($info){
                            continue;
                        }

                        // 插入数据
                        $ret = \App\Order\Models\OrderGoodsInstalment::insert($data);
                        if(!$ret){
                            $arr[$item['id']] = $item;
                        }

                    }

                }

                $page++;
            }
            if(count($arr)>0){
                LogApi::notify("订单分期数据导入失败",$arr);
            }
            $bar->finish();
            echo "导入成功（{$_count1},{$_count2}）";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
