<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class ImportHistoryInstalmentRepayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstalmentRepayment';

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

        $total = \DB::connection('mysql_01')->table('zuji_instalment_prepayment')
            ->where([
                ['prepayment_status', "=", 1],
            ])->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit          = 30;
            $page           = 1;
            $affect_num     = 0;
            $totalpage      = ceil($total/$limit);


            do {
                $result = \DB::connection('mysql_01')->table('zuji_instalment_prepayment')
                    ->select('order_no','term','payment_amount','create_time')
                    ->where([
                        ['prepayment_status', "=", 1],
                    ])
                    ->forPage($page,$limit)
                    ->orderBy('prepayment_id', 'ASC')
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item) {

                    // 已经支付成功 则跳过
                    $instalmentInfo = \App\Order\Models\OrderGoodsInstalment::where([
                        ['order_no', '=', $item['order_no']],
                        ['term', '=', $item['term']],
                    ])->first();
                    $instalmentInfo = objectToArray($instalmentInfo);

                    if(!$instalmentInfo){
                        continue;
                    }

                    $data = [
                        'payment_amount'    => !empty($item['payment_amount']) ? $item['payment_amount']/100 : "",
                        'payment_time'      => !empty($item['create_time']) ? $item['create_time'] : "",
                        'pay_type'          => 1,
                    ];

                    $ret = \App\Order\Models\OrderGoodsInstalment::where([
                            ['order_no', '=', $item['order_no']],
                            ['term', '=', $item['term']],
                        ])
                        ->update($data);
                    if($ret){
                        ++$affect_num;
                    }

                    $bar->advance();

                }


                $page++;

            } while ($page <= $totalpage);

            $bar->finish();
            echo "导入成功:" . $affect_num . '个数据';die;

        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
