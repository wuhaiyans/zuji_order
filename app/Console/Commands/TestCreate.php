<?php

namespace App\Console\Commands;

use App\Order\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use \App\Order\Modules\Repository\OrderUserAddressRepository;
use \App\Order\Modules\Repository\OrderUserCertifiedRepository;

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
        try{
            DB::beginTransaction();
            $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->select('*')->where(['business_key'=>1])->limit(5)->get();
            $orders=objectToArray($datas01);

            foreach ($orders as $k=>$v){
                $order_type =1;
                if($v['appid'] == 36 || $v['appid'] == 90 || $v['appid'] == 91 || $v['appid'] == 92){
                    $order_type =3;
                }
                $res =$this->getStatus($v['status']);
                var_dump($res);die;
                $newData =[
                    'order_no'=>$v['order_no'], //订单编号
                    'mobile'=>$v['mobile'],   //用户手机号
                    'user_id'=>$v['user_id'],  //订单类型
                    'order_type'=>$order_type, //订单类型 1线上订单2门店订单 3小程序订单
                    'order_status'=>$res['order_status'],//
                    'freeze_type'=>$res['freeze_type'],//
                    'pay_type'=>$v['payment_type_id'],//
                    'zuqi_type'=>$v['order_no'],//
                    'remark'=>$v['order_no'],//
                    'order_amount'=>$v['order_no'],//订单实际总租金
                    'goods_yajin'=>$v['order_no'],//商品总押金金额
                    'discount_amount'=>$v['order_no'],//商品优惠总金额
                    'order_yajin'=>$v['order_no'],//实付商品总押金金额
                    'order_insurance'=>$v['order_no'],//意外险总金额
                    'coupon_amount'=>$v['order_no'],//优惠总金额
                    'create_time'=>$v['order_no'],//
                    'update_time'=>$v['order_no'],//
                    'pay_time'=>$v['order_no'],//
                    'confirm_time'=>$v['order_no'],//
                    'delivery_time'=>$v['order_no'],//
                    'appid'=>$v['order_no'],//
                    'channel_id'=>$v['order_no'],//
                    'receive_time'=>$v['order_no'],//
                    'complete_time'=>$v['order_no'],//

                ];
                $res =Order::create($newData);
                if(!$res->getQueueableId()){
                    DB::rollBack();
                    echo "导入失败1";die;
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
        $ret = OrderUserAddressRepository::add($data);
        if($ret){
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
        $ret = OrderUserCertifiedRepository::add($data);
        if($ret){
            return true;
        }
        return false;
    }
}
