<?php
/**
 *小程序风控信息导入
 *
 *将旧订单系统小程序表数据导入到新订单系统（风控信息）
 * @author      zhangjinhui<15116906320@163.com>
 * @since        1.0
 */
namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoryOrderMiniRisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryOrderMiniRisk';

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
     * mysql_01 为阿里云 zuji库
     * mysql_02 为阿里云 zuji2库
     * @return mixed
     */
    public function handle()
    {
        //小程序查询数据表
        $total = \DB::connection('mysql')->table('order_mini_info')->count();
        $i = 0;
        $bar = $this->output->createProgressBar($total);
        try {
            set_time_limit(0);//0表示不限时
            $new_order_mini_info = \DB::connection('mysql')->table('order_mini_info')->select('zm_grade', 'order_no')->get();
//            $new_order_mini_info = objectToArray($new_order_mini_info);
            foreach($new_order_mini_info as $key=>$val){
                //入库小程序的风控信息
                $riskData =[
                    'decision' => $val->zm_grade,
                    'order_no'=>$val->order_no,  // 编号
                    'score' => 0,
                    'strategies' =>'',
                    'type'=>'zhima_score',
                ];
                $id = \App\Order\Modules\Repository\OrderRiskRepository::add($riskData);
                if (!$id) {
                    $i++;
                    continue;
                }
                $bar->advance();
            }
            $bar->finish();
            echo '失败次数'.$i;
            $this->info('导入小程序风控数据成功');
        }catch(\Exception $e){
            \App\Lib\Common\LogApi::debug('小程序风控数据导入异常', $e->getMessage());
            $this->error($e->getMessage());
            die;
        }
    }







}
