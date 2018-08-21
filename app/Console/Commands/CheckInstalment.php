<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class CheckInstalment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CheckInstalment';

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
    public function handle(){

        $total = \App\Order\Models\OrderGoodsInstalment::where([
            ['order_no', 'not like' , 'A%']
        ])->count();
        $bar = $this->output->createProgressBar($total);
        try{

            $limit  = 500;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];

            do {
                $result = \App\Order\Models\OrderGoodsInstalment::where([['order_no', 'not like' , 'A%']])
                    ->select('id','order_no','term')
                    ->forPage($page,$limit)
                    ->orderBy('id', 'DESC')
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item) {

                    // 查询旧系统分期信息    term
                    $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')
                        ->where(['order_no'=>$item['order_no']])
                        ->first();
                    $orderInfo = objectToArray($orderInfo);
                    if(!$orderInfo){
                        continue;
                    }

                    // 查询旧系统 订单信息  order_no
                    $oldInstalment = \DB::connection('mysql_01')->table('zuji_order2_instalment')
                        ->select('amount','discount_amount','status')
                        ->where([
                            ['order_id', '=',  $orderInfo['order_id']],
                            ['term', '=', $item['term']],
                        ])
                        ->first();
                    $oldInstalment = objectToArray($oldInstalment);
                    if(!$oldInstalment){
                        continue;
                    }

                    $original_amount = $oldInstalment['amount'] / 100;

                    if($oldInstalment['status'] == 2){
                        $payment_amount  = ($oldInstalment['amount'] - $oldInstalment['discount_amount']) > 0 ? $oldInstalment['amount'] - $oldInstalment['discount_amount'] : 0;
                    }else{
                        $payment_amount  =  0;
                    }

                    $payment_amount  = $payment_amount / 100;
                    $data = [
                        'original_amount'   => $original_amount,    //原始金额（元）
                        'discount_amount'   => 0,                   //原始优惠金额（元）
                        'payment_amount'    => $payment_amount,     //原始优惠金额（元）
                    ];

                    // 更新新系统 分期信息表
                    $ret = \App\Order\Models\OrderGoodsInstalment::where(
                        ['id'=>$item['id']]
                    )->update($data);
                    if($ret){
                        $arr[] = $item['id'];
                    }

                    $bar->advance();
                }

                $page++;
//                sleep(2);
            } while ($page <= $totalpage);
            if(count($arr)>0){
                LogApi::notify("分期备注信息修改",$arr);
            }
            $bar->finish();
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
