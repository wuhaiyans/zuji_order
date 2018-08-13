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
        $appid =[
            1,2,3,4,7,8,9,11,12,13,14,15,16,18,21,22,28,
            40,41,42,43,44,45,46,47,48,49,
            50,51,52,53,54,55,56,57,58,59,
            60,61,62,63,64,65,66,67,68,69,
            70,71,72,73,74,75,76,77,78,79,
            80,81,82,83,84,85,86,87,88,89,
            93,94,95,96,97,98,122,123,131,132,
        ];
        $where = [
            ['business_key','<>',10],
        ];
        $total = DB::connection('mysql_01')->table("zuji_order2")->where($where)->whereNotIn("appid",$appid)->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 1000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {

                $orderList = DB::connection('mysql_01')->table('zuji_order2')->where($where)->whereNotIn("appid",$appid)->forPage($page,$limit)->get();
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
                LogApi::notify("订单用户地址信息导入失败",$arr);
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

}
