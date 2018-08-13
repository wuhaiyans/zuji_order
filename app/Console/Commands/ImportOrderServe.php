<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Order\Models\OrderGoodsUnit;

class ImportOrderServe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderServe';

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
        $this->conn =\DB::connection('mysql_01');

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $appid =[
            130,92,91,90,36
        ];
        $where = [
            ['service_id','>',0],
            ['business_key','<>',10],
        ];
        $total = DB::connection('mysql_01')->table("zuji_order2")->where($where)->whereIn("appid",$appid)->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 5000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $orderList =DB::connection('mysql_01')->table("zuji_order2")->where($where)->whereIn("appid",$appid)->forPage($page,$limit)->get();
                $orderList =objectToArray($orderList);
                $orderList = array_keys_arrange($orderList,"order_no");
                $serviceIds = array_column($orderList,"service_id");

                $serviceList =\DB::connection('mysql_01')->table("zuji_order2_service")->wherein("service_id",$serviceIds)->get();
                $serviceList =objectToArray($serviceList);
                $serviceList = array_keys_arrange($serviceList,"service_id");

                foreach ($orderList as $k=>$v) {
                    if($serviceList[$v['service_id']]){
                        if(intval($v['create_time']) >= 1532563200){
                            $userInfo =$this->getOrderUserId($v['mobile']);
                            $userId = $userInfo['id'];
                        }else{
                            $userId = $serviceList[$v['service_id']]['user_id'];
                        }
                        $data = [
                            'order_no'=>$v['order_no'],
                            'goods_no'=>$v['goods_id'],
                            'user_id'=>$userId,
                            'unit'=>$v['zuqi_type'],
                            'unit_value'=>$v['zuqi'],
                            'begin_time'=>$serviceList[$v['service_id']]['begin_time'],
                            'end_time'=>$serviceList[$v['service_id']]['end_time'],
                        ];
                        $ret = OrderGoodsUnit::updateOrCreate($data);
                        if(!$ret->getQueueableId()){
                            $arr[$v['order_no']] = $data;
                        }
                    }
                    else{
                        $arr[$v['order_no']] = $v;
                    }
                    $bar->advance();
                }
                $page++;
                sleep(1);
            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单服务周期导入失败",$arr);
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

    /**
     * 获取用户信息
     * @param $mobile 用户手机号
     * @return array 用户信息
     */
    public function getOrderUserId($mobile){

        $datas01 = $this->conn->table('zuji_member')->select('*')->where(['mobile'=>$mobile])->first();
        $arr=[];
        if($datas01){
            $arr =objectToArray($datas01);
        }
        return $arr;
    }
}
