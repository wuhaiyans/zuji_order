<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class ImportHistoryInstalmentRepay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstalmentRepay {--order_no=} {--times=}';

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

            $arr = [];
            $arr2 = [];

            $order_no   = $this->option('order_no');
            $times      = $this->option('times');

            $order_no   = explode(',',$order_no);
            $times      = explode(',',$times);

            $newOrderNo = array();
            if ($order_no)  {

                    foreach ($order_no as $keys=>$values)   {

                        $newOrderNo[$values] = $times[$keys];
                    }

            }


//            p($newOrderNo);


//        $newOrderNo = [
//                '201805280001329'   => '1',
//                '201805020002676'   => '2',
//                '201805020003589'   => '2',
//                '2018050900063'     => '2',
//                '201804010004515'   => '3',
//                '201803310003652'   => '3',
//                '201803210004642'   => '3',
//                '201804110002884'   => '3',
//                '2018022100017'     => '4',
//                '201802220002747'   => '4',
//                '201802270001010'   => '4',
//                '20180220000967'    => '4',
//                '201802270001310'   => '4'
//
//            ];

            try{



                foreach($newOrderNo as $order_no => $times){

                    $instalmentInfo = \App\Order\Models\OrderGoodsInstalment::query()
                        ->where([
                            ["order_no", "=", $order_no],
                            ["times", "=", $times],
                        ])->first();
                    $instalmentInfo = objectToArray($instalmentInfo);
                    if(!$instalmentInfo){
                        continue;
                    }
                    // 修改数据
                    $data = [
                        'status'        => "2",
                        'pay_type'      => "1",
                    ];
                    $ret = \App\Order\Models\OrderGoodsInstalment::where([
                        ["order_no", "=", "".$order_no.""],
                        ["times", "=", "".$times.""],
                    ])->update($data);
                    if(!$ret){
                        $arr[] = $order_no;
                    }

                    // 添加记录
                    $recordData = [
                        'instalment_id'     => $instalmentInfo['id'],
                        'status'            => 2,
                    ];
                    $result = \App\Order\Models\OrderGoodsInstalmentRecord::insert($recordData);
                    if(!$result){
                        $arr2[] = $order_no;
                    }
                }


                p($arr,1);
                p($arr2);
            }catch (\Exception $e){
                echo $e->getMessage();
            }
    }





}
