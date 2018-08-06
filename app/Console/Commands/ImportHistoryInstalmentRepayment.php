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
                ['create_time', ">", '1530806400'],
                ['prepayment_status', "=", 1],
            ])->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 300;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $result = \DB::connection('mysql_01')->table('zuji_instalment_prepayment')
                    ->where([
                        ['create_time', ">", '1530806400'],
                        ['prepayment_status', "=", 1],
                    ])
                    ->forPage($page,$limit)
                    ->orderBy('prepayment_id', 'ASC')
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item) {

                    $data = [
                        'business_no'       => $item['trade_no'],
                        'payment_amount'    => $item['payment_amount']/100,
                        'status'            => 2,
                        'payment_time'      => $item['create_time'],
                        'remark'            => '旧系统提前还款数据导入',
                        'pay_type'          => 1,
                    ];
                    $ret = \App\Order\Models\OrderGoodsInstalment::where([
                            ['order_no', '=', $item['order_no']],
                            ['term', '=', $item['term']],
                        ])
                        ->update($data);
                    if(!$ret){
                        $arr[] = $item['instalment_id'];
                    }

                    $bar->advance();
                }



                $page++;
                sleep(2);
            } while ($page <= $totalpage);
            if(count($arr)>0){
                LogApi::notify("分期主动还款修改",$arr);
            }
            $bar->finish();
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
