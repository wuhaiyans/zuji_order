<?php

namespace App\Console\Commands;

//use App\Lib\Common\LogApi;
use App\Common\LogApi;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderCreater;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ImportGoodsInsuranceCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportGoodsInsuranceCost';

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
        $this->conn =\DB::connection('mysql_01');

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $total = OrderGoods::query()->count();
        $bar = $this->output->createProgressBar($total);
        try{

            $limit = 1000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];

            do {

                $datas01 = OrderGoods::query()->forPage($page,$limit)->get();
                $goods=objectToArray($datas01);
                foreach ($goods as $k=>$v){

                    //获取意外险成本价
                    //获取spu信息
                    $spu_info =$this->getSpuInfo($v['prod_id']);
                    if(empty($spu_info)){
                        continue;
                    }
                    $updateData=[
                        'insurance_cost'=>$spu_info['yiwaixian_cost'],
                    ];
                    $res =OrderGoods::where('id','=',$v['id'])->update($updateData);
                    if(!$res){
                        $arr[]=$v['goods_no'];
                    }

                    $bar->advance();
                }
                ++$page;

            } while ($page <= $totalpage);
            $bar->finish();
//            if(count($arr)>0){
//               //LogApi::notify("ImportGoodsInsuranceCost",$arr);
//                echo "部分导入成功";die;
//            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

    /**
     * 获取SPU
     * @param $spu_id
     * @return array
     */
    public function getSpuInfo($spu_id){

        $datas01 = $this->conn->table('zuji_goods_spu')->select('yiwaixian_cost')->where(['id'=>$spu_id])->first();
        return objectToArray($datas01);
    }


}
