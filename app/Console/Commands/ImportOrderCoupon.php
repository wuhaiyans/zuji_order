<?php

namespace App\Console\Commands;

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
        try{
            DB::beginTransaction();
            $datas01 = \DB::connection('mysql_01')->table('zuji_order2_coupon')->leftJoin('zuji_order2','zuji_order2.order_id','=','zuji_order2_coupon.order_id')->limit(1)->get();
            $coupons=objectToArray($datas01);
            foreach ($coupons as $k=>$v) {
                $couponData = [
                    'coupon_no' => $v['coupon_no'],
                    'coupon_id' => $v['coupon_id'],
                    'discount_amount' => $v['discount_amount'],
                    'coupon_type' => $v['coupon_type'],
                    'coupon_name' => $v['coupon_name'],
                    'order_no' => $v['order_no'],
                ];
                $res = OrderCoupon::updateOrCreate($couponData);
                if (!$res->getQueueableId()) {
                    DB::rollBack();
                    echo "订单优惠券导入失败:" . $v['order_no'];
                    die;
                }
            }

            DB::commit();
            echo "导入成功";die;
        }catch (\Exception $e){
            DB::rollBack();
            echo $e->getMessage();
            die;
        }
    }
}
