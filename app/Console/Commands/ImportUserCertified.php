<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderUserCertified;

class ImportUserCertified extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportUserCertified';

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
        $total = DB::connection('mysql_01')->table("zuji_order2")->where('business_key','=',1)->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 5000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $orderList = DB::connection('mysql_01')->table('zuji_order2')->where('business_key','=',1)->forPage($page,$limit)->get();
                $orderList =objectToArray($orderList);

                foreach ($orderList as $k=>$v) {
                    if(ImportOrder::isAllowImport($v['order_no'])){
                        $data = [
                            'order_no'=>$v['order_no'],
                            'certified'=>$v['credit']>0?1:0,
                            'certified_platform'=>$v['certified_platform'],
                            'credit'=>$v['credit'],
                            'score'=>0,
                            'risk'=>0,
                            'face'=>0,
                            'realname'=>$v['realname'],
                            'cret_no'=>$v['cert_no'],
                            'create_time'=>$v['create_time'],
                        ];
                        $ret = OrderUserCertified::updateOrCreate($data);
                        if(!$ret->getQueueableId()){
                            $arr[$v['order_no']] = $data;
                        }
                        $bar->advance();
                    }
                    else{
                        $arr[$v['order_no']] = $v;
                    }

                }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单用户信用信息导入失败",$arr);
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
