<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:daoru';

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
        $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->select('*')->first();
        $a=objectToArray($datas01);
        var_dump($a);

    }
    public static function list($limit, $page=1)
    {
        return KnightInfo::paginate($limit, ['*'], 'page', $page);
    }
    //订单状态转换
    public function getStatus($status){
        $array = [];
        switch($status){
            //已下单
            case 1:
                $array = ['order_status'=>1, 'freeze_type'=>0,'goods_status'=>0
                ];
                break;
            //已取消
            case 2:
                $array = ['order_status'=>7,'freeze_type'=>0,'goods_status'=>0];
                break;
            //订单关闭
            case 3:
                $array = ['order_status'=>8,'freeze_type'=>0,'goods_status'=>0];
                break;
            //租用中
            case 4:
                $array = ['order_status'=>6,'freeze_type'=>10,'goods_status'=>0];
                break;
            //已支付
            case 7:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0];
                break;
            //退款中
            case 9:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1];
                break;
            //已退款
            case 10:
                $array = [ 'order_status'=>8,'freeze_type'=>21,'goods_status'=>0];
                break;
            //已发货
            case 11:
                $array = [ 'order_status'=>5,'freeze_type'=>0,'goods_status'=>0];
                break;
            //用户拒签
            case 12:
                $array = [ 'order_status'=>5,'freeze_type'=>20,'goods_status'=>1];
                break;
            //退货审核中
            case 13:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1];
                break;
            //退货中
            case 14:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1];
                break;
            //平台已收货
            case 15:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1];
                break;
            //检测合格
            case 16:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1];
                break;
            //检测不合格
            case 17:
                $array = [ 'order_status'=>6,'freeze_type'=>20,'goods_status'=>1];
                break;
            //换货中
            case 18:
                $array = [ 'order_status'=>6,'freeze_type'=>30,'goods_status'=>4];
                break;
            //回寄中
            case 19:
                $array = [ 'order_status'=>6,'freeze_type'=>30,'goods_status'=>4];
                break;
            //买断中
            case 20:
                $array = [ 'order_status'=>6,'freeze_type'=>50,'goods_status'=>3];
                break;
            //已买断
            case 21:
                $array = [ 'order_status'=>9,'freeze_type'=>51,'goods_status'=>0];
                break;
            //资金已授权
            case 22:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0];
                break;
            //资金已解冻
            case 23:
                $array = [ 'order_status'=>8,'freeze_type'=>21,'goods_status'=>0];
                break;
            //用户归还
            case 25:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>0];
                break;
            //已完成
            case 26:
                $array = [ 'order_status'=>9,'freeze_type'=>0,'goods_status'=>0];
                break;
        }
        return $array;
    }
}
