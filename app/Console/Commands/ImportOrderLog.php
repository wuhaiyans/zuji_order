<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderCoupon;
use App\Order\Models\OrderLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOrderLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderLog';

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
        $total = \DB::connection('mysql_01')->table('zuji_order2_log')->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 5000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                    $datas01 = \DB::connection('mysql_01')->table('zuji_order2_log')->forPage($page,$limit)->get();

                    $logs=objectToArray($datas01);

                    foreach ($logs as $k=>$v) {
                        if(!ImportOrder::isAllowImport($v['order_no'])){
                            continue;
                        }
                        $logData = [
                            'order_no' => $v['order_no'],
                            'action' => $v['action'],
                            'operator_id' => $v['operator_id'],
                            'operator_name' => $v['operator_name'],
                            'operator_type' => $v['operator_type'],
                            'msg' => $v['msg'],
                            'system_time' => $v['system_time'],
                        ];
                        $res = OrderLog::updateOrCreate($logData);
                        if (!$res->getQueueableId()) {
                            $arr[$v['order_no']] =$logData;
                        }
                        $bar->advance();
                    }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单日志信息导入失败",$arr);
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }
}
