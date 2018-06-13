<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderGoodsUnit;

class ImportOrderServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderServer';

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
        try{
            $limit = 10;
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
                            'order_no'=>$orderList['order_no'],
                            'goods_no'=>"",
                            'user_id'=>$serviceList[$v['order_no']]['user_id'],
                            'unit'=>$v['zuqi_type'],
                            'unit_value'=>$v['zuqi'],
                            'begin_time'=>$serviceList[$v['order_no']]['begin_time'],
                            'end_time'=>$serviceList[$v['order_no']]['end_time'],
                        ];
                        $ret = OrderGoodsUnit::updateOrCreate($data);
                        if(!$ret->getQueueableId()){
                            $arr[$v['order_no']] = $data;
                        }
                    }
                    else{
                        $arr[$v['order_no']] = $data;
                    }
                }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            if(count($arr)>0){
                LogApi::notify("订单服务周期导入失败",$arr);
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
