<?php

namespace App\Console\Commands;

//use App\Lib\Common\LogApi;
use App\Lib\Common\LogApi;
use App\Lib\Risk\Yajin;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderCreater;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class UpdateRiskYajin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:UpdateRiskYajin';

    /**
     *
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    private $conn;

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
        //有效订单
        $orderStatus =[1,2,3,4,5,6];
        $total = Order::query()->whereIn("order_status",$orderStatus)->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 100;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            $orderId =0;
            do {

                $datas01 = Order::query()->whereIn("order_status",$orderStatus)->forPage($page,$limit)->get();
                $orders=objectToArray($datas01);
                foreach ($orders as $k=>$v){
                    $jianmian = ($v['goods_yajin']-$v['order_yajin'])*100;
                    //请求押金接口
                    try{
                        $yajin = Yajin::MianyajinReduce(['user_id'=>$v['user_id'],'jianmian'=>$jianmian,'order_no'=>$v['order_no']]);
                    }catch (\Exception $e){
                        $arr [] = $v['order_no'];
                    }

                    $bar->advance();
                }
                sleep(1);
                ++$page;

            } while ($page <= $totalpage);
            $bar->finish();
            if(empty($arr)){
                echo "发送成功";die;
            }
            LogApi::error("MianyajinReduce-error:",$arr);
            echo "发送失败";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
