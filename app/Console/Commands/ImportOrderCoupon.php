<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderCoupon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOrderCoupon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderCoupon';

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
        $total = \DB::connection('mysql_01')->table('zuji_order2_coupon')->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit = 5000;
            $page =1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                    $datas01 = \DB::connection('mysql_01')->table('zuji_order2_coupon')->leftJoin('zuji_order2','zuji_order2.order_id','=','zuji_order2_coupon.order_id')->forPage($page,$limit)->get();

                    $coupons=objectToArray($datas01);

                    foreach ($coupons as $k=>$v) {

                        if(empty($v['order_no'])){
                            continue;
                        }
                        $couponData = [
                            'coupon_no' => $v['coupon_no'],
                            'coupon_id' => $v['coupon_id'],
                            'discount_amount' => $v['discount_amount']/100,
                            'coupon_type' => $v['coupon_type'],
                            'coupon_name' => $v['coupon_name'],
                            'order_no' => $v['order_no'],
                        ];
                        $res = OrderCoupon::updateOrCreate($couponData);
                        if (!$res->getQueueableId()) {
                            $arr[$v['order_no']] =$couponData;
                        }
                        $bar->advance();
                    }
                $page++;

            } while ($page <= $totalpage);
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("订单优惠券信息导入失败",$arr);
                echo "部分导入成功";die;
            }
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }
    }
}
