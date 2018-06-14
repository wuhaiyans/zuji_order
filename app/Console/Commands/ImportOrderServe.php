<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderGoodsUnit;

class ImportOrderServe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderServe';

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
        $where = [
            ['business_key','=',1,],
            ['service_id','>',0]
        ];
        $total = DB::connection('mysql_01')->table("zuji_order2")->where($where)->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 5000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $orderList =DB::connection('mysql_01')->table("zuji_order2")->where($where)->forPage($page,$limit)->get();
                $orderList =objectToArray($orderList);
                $orderList = array_keys_arrange($orderList,"order_no");
                $serviceIds = array_column($orderList,"service_id");

                $serviceList =\DB::connection('mysql_01')->table("zuji_order2_service")->wherein("service_id",$serviceIds)->get();
                $serviceList =objectToArray($serviceList);
                $serviceList = array_keys_arrange($serviceList,"service_id");

                foreach ($orderList as $k=>$v) {
                    if($serviceList[$v['service_id']]){
                        $data = [
                            'order_no'=>$v['order_no'],
                            'goods_no'=>$v['goods_id'],
                            'user_id'=>$serviceList[$v['service_id']]['user_id'],
                            'unit'=>$v['zuqi_type'],
                            'unit_value'=>$v['zuqi'],
                            'begin_time'=>$serviceList[$v['service_id']]['begin_time'],
                            'end_time'=>$serviceList[$v['service_id']]['end_time'],
                        ];
                        $ret = OrderGoodsUnit::updateOrCreate($data);
                        if(!$ret->getQueueableId()){
                            $arr[$v['order_no']] = $data;
                        }
                    }
                    else{
                        $arr[$v['order_no']] = $v['order_no'];
                    }
                    $bar->advance();
                }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单服务周期导入失败",$arr);
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
