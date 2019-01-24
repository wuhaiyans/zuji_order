<?php

namespace App\Console\Commands;

//use App\Lib\Common\LogApi;
use App\Lib\Common\LogApi;
use App\Lib\Goods\Goods;
use App\Lib\Risk\Yajin;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Modules\Inc\GivebackAddressStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderCreater;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class ImportOrderReturnAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOrderReturnAddress';

    /**
     *
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    private $conn;

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
            $orderStatus =[7,8,9,10];
            $total = DB::table('order_goods')
                ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods.order_no')
                ->where("order_goods.id","<=","122857")->whereNotIn("order_info.order_status",$orderStatus)->count();

            $bar = $this->output->createProgressBar($total);
            $limit = 1000;
            $page = 1;
            $totalpage = ceil($total / $limit);
            $arr = [];
            $orderId = 0;
            do {

                $datas01 = DB::table('order_goods')
                    ->leftJoin('order_info', 'order_info.order_no', '=', 'order_goods.order_no')
                    ->where("order_goods.id","<=","122857")->whereNotIn("order_info.order_status",$orderStatus)->forPage($page, $limit)->get();
                $goods = objectToArray($datas01);
                foreach ($goods as $k => $v) {
                    if(isset(GivebackAddressStatus::SPU_ADDRDSS_TYPE[$v['prod_id']])){
                        $returnInfo = GivebackAddressStatus::getGivebackAddress($v['prod_id']);
                    }else{
                        try{
                            $goodsArr = Goods::getSkuList( [$v['zuji_goods_id']] ,'17600224881');
                            $returnInfo['giveback_address']=$goodsArr[$v['zuji_goods_id']]['return_address_info']['return_address_value']??'';//还机回寄地址
                            $returnInfo['giveback_username']=$goodsArr[$v['zuji_goods_id']]['return_address_info']['return_name']??'';//还机回寄收货人姓名
                            $returnInfo['giveback_tel'] = $goodsArr[$v['zuji_goods_id']]['return_address_info']['return_phone']??'';//还机回寄收货人电话'
                            if($returnInfo['giveback_address'] =='' || $returnInfo['giveback_username'] =='' || $returnInfo['giveback_tel']==''){
                                $returnInfo = GivebackAddressStatus::getGivebackAddress($v['prod_id']);
                            }
                        }catch (\Exception $e){
                            $arr ['exception'][] = $v['prod_id'];
                            $returnInfo = GivebackAddressStatus::getGivebackAddress($v['prod_id']);
                        }
                    }
                    $GoodsExtend = OrderGoodsExtend::where('order_no', '=', $v['order_no'])->first();
                    if ($GoodsExtend) {
                        $orderGoodsExtend = $GoodsExtend->toArray();
                        $GoodsExtend->return_address_value = $returnInfo['giveback_address'] ?? $orderGoodsExtend['giveback_address'];
                        $GoodsExtend->return_name = $returnInfo['giveback_username'] ?? $orderGoodsExtend['giveback_username'];
                        $GoodsExtend->return_phone = $returnInfo['giveback_tel'] ?? $orderGoodsExtend['giveback_tel'];
                        $GoodsExtend->update_time = time();
                        $b = $GoodsExtend->save();
                        if (!$b) {
                            $arr ['update'][] = $v['order_no'];
                        }
                    } else {
                        $returnInfo = [
                            'order_no' => $v['order_no'],
                            'goods_no' => $v['goods_no'],
                            'return_name' => $returnInfo['giveback_username'] ?? '',
                            'return_phone' => $returnInfo['giveback_tel'] ?? '',
                            'return_address_value' => $returnInfo['giveback_address'] ?? '',
                            'create_time' => time()
                        ];
                        $info = OrderGoodsExtend::create($returnInfo);
                        $b = $info->getQueueableId();
                        if (!$b) {
                            $arr ['insert'][] = $v['order_no'];
                        }
                    }

                    $bar->advance();
                }
                sleep(1);
                ++$page;

            } while ($page <= $totalpage);
            $bar->finish();
            LogApi::info("ImportOrderReturnAddress:", $arr);
            echo "发送成功";
            die;


        }


}
