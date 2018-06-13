<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderRisk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOrderYidun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderYidun';

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
        $total = \DB::connection('mysql_01')->table('zuji_order2_yidun')->count();
        try{
            $limit = 1;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                    $datas01 = \DB::connection('mysql_01')->table('zuji_order2_yidun')->leftJoin('zuji_order2','zuji_order2.order_id','=','zuji_order2_yidun.order_id')->forPage($page,$limit)->get();
                    $yiduns=objectToArray($datas01);
                    foreach ($yiduns as $k=>$v) {
                        $riskData = [
                            'order_no' => $v['order_no'],
                            'decision' => $v['decision'],
                            'score' => $v['score'],
                            'strategies' => $v['strategies'],
                            'type' => 'yidun',
                        ];
                        $res = OrderRisk::updateOrCreate($riskData);
                        if (!$res->getQueueableId()) {
                            $arr[$v['order_no']] =$riskData;
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
