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
        try{
            DB::beginTransaction();
            $datas01 = \DB::connection('mysql_01')->table('zuji_order2')->select('*')->where(['business_key'=>1])->limit(5)->get();
            $orders=objectToArray($datas01);

            foreach ($orders as $k=>$v){
                //获取商品
                $goods = \DB::connection('mysql_01')->table('zuji_order2_goods')->select('*')->where(['order_id'=>$v['order_id']])->first();
                $goods_info=objectToArray($goods);

                $goodsArr = Goods::getSkuList([$goods_info['sku_id']]);
                if (!is_array($goodsArr)) {
                    DB::rollBack();
                    echo "商品接口获取失败:".$v['order_no'];die;
                }



                //订单服务周期
                $service =\DB::connection('mysql_01')->table("zuji_order2_service")->where(['order_no'=>$v['order_no']])->get()->first();
                if($service){
                    $serviceData = [
                        'order_no'=>$service['order_no'],
                        'goods_no'=>$goodsNo,
                        'user_id'=>$service['user_id'],
                        'unit'=>$v['zuqi_type'],
                        'unit_value'=>$v['zuqi'],
                        'begin_time'=>$service['begin_time'],
                        'end_time'=>$service['end_time'],
                    ];
                    $ret = OrderGoodsUnit::updateOrCreate($serviceData);
                    if(!$ret->getQueueableId()){
                        DB::rollBack();
                        echo "插入服务周期失败:".$v['order_no'];die;
                    }
                }

                $goodsData =[
                    'order_no'=>$v['order_no'],
                    'goods_name'=>$v['goods_name'],
                    'zuji_goods_id'=>$goods_info['sku_id'],
                    'zuji_goods_sn'=>$goodsArr[$goods_info['sku_id']]['sku_info']['sn'],
                    'goods_no'=>$goodsNo,
                    'goods_thumb'=>$goods_info['thumb'],
                    'prod_id'=>$goods_info['spu_id'],
                    'prod_no'=>$goodsArr[$goods_info['sku_id']]['spu_info']['sn'],
                    'brand_id'=>$goods_info['brand_id'],
                    'category_id'=>$goods_info['category_id'],
                    'machine_id'=>$goodsArr[$goods_info['sku_id']]['sku_info']['machine_id'],
                    'user_id'=>$v['user_id'],
                    'quantity'=>1,
                    'goods_yajin'=>($goods_info['yajin']+$goods_info['mianyajin'])/100,
                    'yajin'=>$goods_info['yajin']/100,
                    'zuqi'=>$goods_info['zuqi'],
                    'zuqi_type'=>$goods_info['zuqi_type'],
                    'zujin'=>$goods_info['zujin']/100,
                    'machine_value'=>empty($goodsArr[$goods_info['sku_id']]['sku_info']['machine_name'])?"":$goodsArr[$goods_info['sku_id']]['sku_info']['machine_name'],
                    'chengse'=>$goods_info['chengse'],
                    'discount_amount'=>0,
                    'coupon_amount'=>$v['discount_amount']/100,
                    'amount_after_discount'=>($goods_info['zuqi']*$goods_info['zujin']-$v['discount_amount'])/100,
                    'edition'=>$goodsArr[$goods_info['sku_id']]['sku_info']['edition'],
                    'business_key'=>0,
                    'business_no'=>'',
                    'market_price'=>$goodsArr[$goods_info['sku_id']]['sku_info']['market_price'],
                    'price'=>($goods_info['zuqi']*$goods_info['zujin']-$v['discount_amount']+$goods_info['yiwaixian']+$goods_info['yajin'])/100,
                    'specs'=>$goods_info['specs'],
                    'insurance'=>$goods_info['yiwaixian']/100,
                    'buyout_price'=>($goodsArr[$goods_info['sku_id']]['sku_info']['market_price']*120 -($goods_info['zuqi']*$goods_info['zujin']/100)),
                    'begin_time'=>0,
                    'end_time'=>0,
                    'weight'=>$goodsArr[$goods_info['sku_id']]['sku_info']['weight'],
                    'goods_status'=>$status['goods_status'],
                    'create_time'=>$goods_info['create_time'],
                    'update_time'=>$goods_info['update_time'],
                ];
                $res =OrderGoods::updateOrCreate($goodsData);
                if(!$res->getQueueableId()){
                    DB::rollBack();
                    echo "商品导入失败:".$v['order_no'];die;
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
