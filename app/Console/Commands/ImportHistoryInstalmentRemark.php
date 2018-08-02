<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class ImportHistoryInstalmentRemark extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InstalmentRemark';

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

        $total = \DB::connection('mysql_01')->table('zuji_order2_instalment_remark')->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 1000;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $result = \DB::connection('mysql_01')->table('zuji_order2_instalment_remark')
                    ->forPage($page,$limit)
                    ->orderBy('id', 'ASC')
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item) {

                    //有记录则跳出
                    $info = \App\Order\Models\OrderGoodsInstalmentRemark::query()
                        ->where([
                            ['id', '=', $item['id']],
                            ['instalment_id', '=', $item['instalment_id']]
                        ])->first();
                    if($info){
                        continue;
                    }

                    // 插入数据
                    $ret = \App\Order\Models\OrderGoodsInstalmentRemark::insert($item);
                    if(!$ret){
                        $arr[$item['id']] = $item;
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
