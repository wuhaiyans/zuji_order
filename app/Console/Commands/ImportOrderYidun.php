<?php

namespace App\Console\Commands;

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
        try{
            DB::beginTransaction();
            $datas01 = \DB::connection('mysql_01')->table('zuji_order2_yidun')->leftJoin('zuji_order2','zuji_order2.order_id','=','zuji_order2_yidun.order_id')->limit(1)->get();
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
                    DB::rollBack();
                    echo "订单风控信息导入失败:" . $v['order_no'];
                    die;
                }
            }

            DB::commit();
            echo "导入成功";die;
        }catch (\Exception $e){
            DB::rollBack();
            echo $e->getMessage();
            die;
        }
    }
}
