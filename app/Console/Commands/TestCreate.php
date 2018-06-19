<?php

namespace App\Console\Commands;

use App\Lib\Channel\Channel;
use App\Lib\Goods\Goods;
use App\Lib\Order\OrderInfo;
use App\Order\Models\Order;
use App\Order\Models\OrderExtend;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderGoodsUnit;
use App\Order\Models\OrderUserAddress;
use App\Order\Models\OrderUserCertified;
use App\Order\Models\OrderVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
//        if ($this->confirm('Do you wish to continue? [y|N]')) {
//            echo $this->argument('user');
//        }
//        $name = $this->anticipate('What is your name?', ['A', 'B']);
//        if($name =="A" || $name =="B"){
//            echo "A or B";die;
//        }
//        echo "error";die;
//        $name = $this->choice('What is your name?', ['A', 'B'], false);
//        echo "YES";die;
        //line,info, comment, question 和 error
 //       $this->line('Display this on the screen');
//        $headers = ['Name', 'Email'];
//        $users = [['tom','12@12']];
//        $this->table($headers, $users);

    }

    //订单状态转换
    public static function getStatus($status,$order_info){
        $array = [];
        $update_time =$order_info['update_time'];
        if($order_info['refund_time']!=0){
            $update_time =$order_info['refund_time'];
        }
        switch($status){
            //已下单
            case 1:
                $array = ['order_status'=>1, 'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //已取消
            case 2:
                $array = ['order_status'=>7,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>$order_info['update_time']];
                break;
            //订单关闭
            case 3:
                $array = ['order_status'=>8,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>$order_info['update_time']];
                break;
            //租用中
            case 4:
                $array = ['order_status'=>6,'freeze_type'=>0,'goods_status'=>10,'complete_time'=>0];
                break;
            //已支付
            case 7:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //退款中
            case 9:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //已退款
            case 10:
                $array = [ 'order_status'=>8,'freeze_type'=>0,'goods_status'=>21,'complete_time'=>$update_time];
                break;
            //已发货
            case 11:
                $array = [ 'order_status'=>5,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //用户拒签
            case 12:
                $array = [ 'order_status'=>5,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //退货审核中
            case 13:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //退货中
            case 14:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //平台已收货
            case 15:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //检测合格
            case 16:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //检测不合格
            case 17:
                $array = [ 'order_status'=>6,'freeze_type'=>1,'goods_status'=>20,'complete_time'=>0];
                break;
            //换货中
            case 18:
                $array = [ 'order_status'=>6,'freeze_type'=>4,'goods_status'=>30,'complete_time'=>0];
                break;
            //回寄中
            case 19:
                $array = [ 'order_status'=>6,'freeze_type'=>4,'goods_status'=>30,'complete_time'=>0];
                break;
            //买断中
            case 20:
                $array = [ 'order_status'=>6,'freeze_type'=>3,'goods_status'=>50,'complete_time'=>0];
                break;
            //已买断
            case 21:
                $array = [ 'order_status'=>9,'freeze_type'=>0,'goods_status'=>51,'complete_time'=>$order_info['update_time']];
                break;
            //资金已授权
            case 22:
                $array = [ 'order_status'=>3,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //资金已解冻
            case 23:
                $array = [ 'order_status'=>8,'freeze_type'=>0,'goods_status'=>21,'complete_time'=>$update_time];
                break;
            //用户归还
            case 25:
                $array = [ 'order_status'=>6,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>0];
                break;
            //已完成
            case 26:
                $array = [ 'order_status'=>9,'freeze_type'=>0,'goods_status'=>0,'complete_time'=>$order_info['update_time']];
                break;
        }
        return $array;
    }
    //用户地址导入
    public function userAddressInsert($order){
        $userAddress = \DB::connection('mysql_01')->table('zuji_order2_address')->select('*')->first();
        if(!$userAddress){
            return false;
        }
        $data = [
            'order_no'=>$order['order_no'],
            'consignee_mobile'=>$userAddress['mobile'],
            'name'=>$userAddress['name'],
            'province_id'=>$userAddress['province_id'],
            'city_id'=>$userAddress['city_id'],
            'area_id'=>$userAddress['country_id'],
            'address_info'=>$userAddress['address'],
            'create_time'=>$order['create_time'],
        ];
        $ret = OrderUserAddress::updateOrCreate($data);
        if($ret->getQueueableId()){
            return true;
        }
        return false;
    }
    //用户信用认证导入
    public function userCertifiedInsert($order){
        $userCertified = \DB::connection('mysql_01')->table('zuji_order2_address')->select('*')->first();
        if(!$userCertified){
            return false;
        }
        $data = [
            'order_no'=>$order['order_no'],
            'certified'=>$order['order_no'],
            'certified_platform'=>$order['certified_platform'],
            'credit'=>$order['credit'],
            'score'=>0,
            'risk'=>0,
            'face'=>0,
            'realname'=>$order['realname'],
            'cret_no'=>$order['cert_no'],
            'create_time'=>$order['create_time'],
        ];
        $ret = OrderUserCertified::updateOrCreate($data);
        if($ret->getQueueableId()){
            return true;
        }
        return false;
    }
    //订单回访数据导入
    public function visit($order){
        if($order['remark_id']>0){
            $data = [
                'order_no' => $order['order_no'],
                'visit_id' => $order['remark_id'],
                'visit_text' => $order['remark'],
                'create_time' => $order['create_time'],
            ];
            $ret =OrderVisit::updateOrCreate($data);
            if(!$ret->getQueueableId()){
                return false;
            }else{
                $res = OrderExtend::updateOrCreate(['order_no'=>$data['order_no'],'field_name'=>"visit","field_value"=>1]);
                if(!$res->getQueueableId()){
                    return false;
                }
            }
        }
        return true;

    }





}
