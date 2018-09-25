<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Lib\Order\OrderInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderUserAddress;

class ImportUserAddress2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportUserAddress2';

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
        $orderNos =[
            '201807140001217',
            '201807190003109',
            '201807210001035',
            '201807210002234',
            '20180725000360',
            '201807260002288',
            '201807260002344',
        ];

        $total = DB::connection('mysql_01')->table("zuji_order2")->whereIn("order_no",$orderNos)->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 10;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {

                $orderList = DB::connection('mysql_01')->table('zuji_order2')->whereIn("order_no",$orderNos)->forPage($page,$limit)->get();
                $orderList =objectToArray($orderList);
                $orderList = array_keys_arrange($orderList,"order_no");
                $orderIds = array_column($orderList,"order_id");

                $addressList = DB::connection('mysql_01')->table("zuji_order2_address")->wherein("order_id",$orderIds)->get();
                $addressList =objectToArray($addressList);
                $addressList = array_keys_arrange($addressList,"order_id");

                foreach ($orderList as $k=>$v) {
                    $bar->advance();
                    if(!empty($addressList[$v['order_id']])){
                        $province = DB::connection('mysql_01')->table("zuji_district")->where('id','=',$addressList[$v['order_id']]['province_id'])->first();
                        $city = DB::connection('mysql_01')->table("zuji_district")->where('id','=',$addressList[$v['order_id']]['city_id'])->first();
                        $area = DB::connection('mysql_01')->table("zuji_district")->where('id','=',$addressList[$v['order_id']]['country_id'])->first();
                        $province = objectToArray($province);
                        $city = objectToArray($city);
                        $area = objectToArray($area);
                        $address = $province['name']." ".$city['name']." ".$area['name']." ".$addressList[$v['order_id']]['address'];
                        $data = [
                            'order_no'=>$v['order_no'],
                            'consignee_mobile'=>$addressList[$v['order_id']]['mobile'],
                            'name'=>$addressList[$v['order_id']]['name'],
                            'province_id'=>$addressList[$v['order_id']]['province_id'],
                            'city_id'=>$addressList[$v['order_id']]['city_id'],
                            'area_id'=>$addressList[$v['order_id']]['country_id'],
                            'address_info'=>$address,
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
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
