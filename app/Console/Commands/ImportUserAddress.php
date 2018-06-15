<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Lib\Order\OrderInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderUserAddress;

class ImportUserAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportUserAddress';

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
            $limit = 1000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $orderList = DB::connection('mysql_01')->table('zuji_order2')->where('business_key','=',1)->forPage($page,$limit)->get();
                $orderList =objectToArray($orderList);
                $orderList = array_keys_arrange($orderList,"order_no");
                $orderIds = array_column($orderList,"order_id");

                $addressList = DB::connection('mysql_01')->table("zuji_order2_address")->wherein("order_id",$orderIds)->get();
                $addressList =objectToArray($addressList);
                $addressList = array_keys_arrange($addressList,"order_id");

                foreach ($orderList as $k=>$v) {
                    $bar->advance();
                    if(!empty($addressList[$v['order_id']])){
                        $data = [
                            'order_no'=>$v['order_no'],
                            'consignee_mobile'=>$addressList[$v['order_id']]['mobile'],
                            'name'=>$addressList[$v['order_id']]['name'],
                            'province_id'=>$addressList[$v['order_id']]['province_id'],
                            'city_id'=>$addressList[$v['order_id']]['city_id'],
                            'area_id'=>$addressList[$v['order_id']]['country_id'],
                            'address_info'=>$addressList[$v['order_id']]['address'],
                            'create_time'=>$v['create_time'],
                        ];
                        $ret = OrderUserAddress::updateOrCreate($data);
                        if(!$ret->getQueueableId()){
                            $arr[$v['order_no']] = $data;
                        }
                    }
                    else{
                        $arr[$v['order_no']] = $v;
                    }
                }
                $page++;
                sleep(2);
            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单用户地址信息导入失败",$arr);
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
