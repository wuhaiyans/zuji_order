<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRiskRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderUserCertified;

class ImportRisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportRisk';

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
     * 获取芝麻认证信息
     * @param $order_no
     * @return array
     */
    public function getOrderZhima($order_no){

        $datas01 = \DB::connection('mysql_01')->table('zuji_zhima_certification')->select('*')->where(['out_order_no'=>$order_no])->first();
        $arr=[];
        if($datas01){
            $arr =objectToArray($datas01);
        }
        return $arr;
    }

    /**
     * 获取订单蚁盾数据
     * @param $order_id
     * @return array
     */
    public function getOrderYidun($order_id){

        $datas01 = \DB::connection('mysql_01')->table('zuji_order2_yidun')->select('*')->where(['order_id'=>$order_id])->first();
        $arr=[];
        if($datas01){
            $arr =objectToArray($datas01);
        }
        return $arr;
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
                    $zhima =$this->getOrderZhima($v['order_no']);
                    if(!empty($zhima)){

                        $riskData =[
                            'decision' => $zhima['zm_grade'],
                            'order_no'=>$v['order_no'],  // 编号
                            'score' => $zhima['zm_score'],
                            'strategies' =>'',
                            'type'=>'zhima_score',
                        ];
                        $id =OrderRiskRepository::add($riskData);
                        if(!$id){
                            $arr[$v['order_no']] = $riskData;
                        }
                    }

                    $yidun =$this->getOrderYidun($v['order_id']);
                    if(!empty($yidun)){
                        $riskData =[
                            'decision' => strtoupper($yidun['decision']),
                            'order_no'=>$v['order_no'],  // 编号
                            'score' => $yidun['score'],
                            'strategies' =>$yidun['strategies'],
                            'type'=>'yidun',
                        ];
                        $id =OrderRiskRepository::add($riskData);
                        if(!$id){
                            $arr[$v['order_no']] = $riskData;
                        }
                    }


                        $bar->advance();

                }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单风控信息",$arr);
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
