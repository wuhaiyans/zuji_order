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

        $appid =[
            1,2,3,4,7,8,9,11,12,13,14,15,16,18,21,22,28,
            40,41,42,43,44,45,46,47,48,49,
            50,51,52,53,54,55,56,57,58,59,
            60,61,62,63,64,65,66,67,68,69,
            70,71,72,73,74,75,76,77,78,79,
            80,81,82,83,84,85,86,87,88,89,
            93,94,95,96,97,98,122,123,131,132,
        ];

        $whereArr[] =['business_key','<>','10'];
        //3点之前非关闭的订单，3点之后所有订单
        $total = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)->whereNotIn("appid",$appid)
            ->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 5000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $orderList = \DB::connection('mysql_01')->table('zuji_order2')->where($whereArr)->whereNotIn("appid",$appid)->forPage($page,$limit)->get();
                $orderList =objectToArray($orderList);

                foreach ($orderList as $k=>$v) {
                        $data = [
                            'order_no'=>$v['order_no'],
                            'certified'=>$v['credit']>0?1:0,
                            'certified_platform'=>$v['certified_platform'],
                            'credit'=>$v['credit'],
                            'card_img'=>'',
                            'deposit_detail'=>'',
                            'deposit_msg'=>'',
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
