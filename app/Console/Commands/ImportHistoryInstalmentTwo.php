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
        $_count3 = 0;
        //$totalSql = 'select count(order_info.id) as num from order_info left join order_goods_instalment ON order_info.order_no=order_goods_instalment.order_no WHERE order_goods_instalment.id IS NULL';


        $total = \App\Order\Models\Order::query()
            ->where([
                ['order_goods_instalment.id', '=', null],
            ])
            ->leftJoin('order_goods_instalment', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
            ->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 10;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];

            while($page <= $totalpage) {

                // 未导入的订单id
                $arr = [];

                $res =  \App\Order\Models\Order::query()
                    ->select('order_info.*')
                    ->where([
                        ['order_goods_instalment.id', '=', null],
                    ])
                    ->leftJoin('order_goods_instalment', 'order_info.order_no', '=', 'order_goods_instalment.order_no')
                    ->orderBy('order_info.id', 'DESC')
                    ->forPage($page,$limit)
                    ->get()->toArray();
                $result = objectToArray($res);


                foreach($result as &$item) {
                    ++$_count1;

                    // 旧系统 订单信息
                    $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_id', 'order_no', 'goods_id', 'user_id', 'zujin')->where(['order_no' => $item['order_no']])->first();
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
                        ->where(['order_id' => $orderInfo['order_id']])
                        ->get()->toArray();
                    $instalmentList = objectToArray($instalment);
                    if($instalmentList == []){
                        ++$_count3;
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

                        //有记录则跳出
                        $info = \App\Order\Models\OrderGoodsInstalment::query()
                            ->where([
                                ['times', '=', $item['times']],
                                ['goods_no', '=', $orderInfo['goods_id']]
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

                    $bar->advance();
                }

                $page++;
            }
            if(count($arr)>0){
                LogApi::notify("订单分期数据导入失败",$arr);
            }
            $bar->finish();
            LogApi::info("分期数据导入",[$_count1,$_count2,$_count3]);
            echo "导入成功（{$_count1},{$_count2},{$_count3}）";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
