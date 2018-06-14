<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderGoodsUnit;

class UpdateOrderServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:UpdateOrderServer';

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
        $total = DB::table("order_goods_unit")->count();
        try{
            $limit = 10;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $unitList =DB::table("order_goods_unit")->forPage($page,$limit)->get();
                $unitList =objectToArray($unitList);
                $unitList = array_keys_arrange($unitList,"order_no");
                $orderNos = array_column($unitList,"order_no");

                $goodsList =\DB::table("order_goods")->wherein("order_no",$orderNos)->get();
                $goodsList =objectToArray($goodsList);
                $goodsList = array_keys_arrange($goodsList,"order_no");

                foreach ($unitList as $k=>$v) {
                    if($goodsList[$v['order_no']]){
                        $data = [
                            'goods_no'=>$goodsList[$v['order_no']]['goods_no'],
                        ];
                        $ret = OrderGoodsUnit::update($data);
                        if(!$ret->getQueueableId()){
                            $arr[$v['order_no']] = $data;
                        }
                    }
                    else{
                        $arr[$v['order_no']] = $data;
                    }
                }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            if(count($arr)>0){
                LogApi::notify("订单服务周期商品编号更新失败",$arr);
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
