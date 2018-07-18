<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderExtend;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderVisit;

class ImportUserVisit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportUserVisit';

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
            ['remark_id','>',0]
        ];
        $total = DB::connection('mysql_01')->table("zuji_order2")->where($where)->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 5000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $orderList = DB::connection('mysql_01')->table('zuji_order2')->where($where)->forPage($page,$limit)->get();
                $orderList =objectToArray($orderList);

                foreach ($orderList as $k=>$v) {
                    if(ImportOrder::isAllowImport($v['order_no'])) {
                        $data = [
                            'order_no' => $v['order_no'],
                            'visit_id' => $v['remark_id'],
                            'visit_text' => $v['remark'],
                            'create_time' => $v['create_time'],
                        ];
                        $ret = OrderVisit::updateOrCreate($data);
                        OrderExtend::updateOrCreate(['order_no' => $v['order_no'], 'field_name' => 'visit', 'field_value' => 1]);
                        if (!$ret->getQueueableId()) {
                            $arr[$v['order_no']] = $data;
                        }
                        $bar->advance();
                    }else{
                        $arr[$v['order_no']] = $v;
                    }
                }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单用户回访数据导入失败",$arr);
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
