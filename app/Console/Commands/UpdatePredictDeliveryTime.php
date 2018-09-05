<?php

namespace App\Console\Commands;

//use App\Lib\Common\LogApi;
use App\Lib\Common\LogApi;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderCreater;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class UpdatePredictDeliveryTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:UpdatePredictDeliveryTime';

    /**
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

        $total = Order::query()->count();
        $bar = $this->output->createProgressBar($total);
        try{

            $limit = 2000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            $orderId =0;
            do {

                $datas01 = Order::query()->forPage($page,$limit)->get();
                $orders=objectToArray($datas01);
                foreach ($orders as $k=>$v){

                    if($v['predict_delivery_time'] ==0){
                        $dateTime =date('Y-m-d',$v['create_time']);
                        //预定发货时间为第二天下午15点
                        $PredictDeliveryTime = strtotime($dateTime)+86400+3600*15;

                        $data['predict_delivery_time'] =$PredictDeliveryTime;
                        $res =Order::where('id','=',$v['id'])->update($data);
                        if(!$res){
                            echo "导入失败";die;
                        }
                        $bar->advance();

                    }


                }
                ++$page;

            } while ($page <= $totalpage);
            $bar->finish();
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
