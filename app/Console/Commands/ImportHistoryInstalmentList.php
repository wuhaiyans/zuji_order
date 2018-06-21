<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class ImportHistoryInstalmentList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstalmentList';

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

        $total = \DB::connection('mysql_01')->table('zuji_instalment_prepayment')->where(['prepayment_status'=>1])->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 300;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $result = \DB::connection('mysql_01')->table('zuji_instalment_prepayment')
                    ->where(['prepayment_status'=>1])
                    ->forPage($page,$limit)
                    ->orderBy('prepayment_id', 'ASC')
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item) {
                    // 查询订单信息
                    $instalmentInfo = \App\Order\Models\OrderGoodsInstalment::find($item['instalment_id']);
                    $instalmentInfo = objectToArray($instalmentInfo);
                    if(!$instalmentInfo){
                        continue;
                    }

                    // 修改数据
                    $data = ['pay_type'=>1];
                    $ret = \App\Order\Models\OrderGoodsInstalment::where(['id'=>$item['instalment_id']])->update($data);

                    if(!$ret){
                        $arr[] = $item['instalment_id'];
                    }
                }

                $bar->advance();

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
