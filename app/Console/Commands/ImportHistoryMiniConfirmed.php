<?php
/**
 *小程序确认订单数据
 *
 *将旧订单系统小程序表数据导入到新订单系统
 * @author      zhangjinhui<15116906320@163.com>
 * @since        1.0
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoryMiniConfirmed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryMiniConfirmed';

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
        //小程序回调数据表
        DB::beginTransaction();
        $total = \DB::connection('mysql_01')->table('zuji_zhima_order_confirmed')
            ->count();
        $bar = $this->output->createProgressBar($total);
        try {
            $old_mini_orders = \DB::connection('mysql_01')->table('zuji_zhima_order_confirmed')->select('*')->get();
            $old_mini_orders = objectToArray($old_mini_orders);
            foreach ($old_mini_orders as $key => $val) {
                unset($val['api_name']);
                unset($val['id']);
                if ($val['notify_type'] != 'ZM_RENT_ORDER_CREATE') {
                    \App\Lib\Common\LogApi::debug('小程序完成 或 扣款 回调记录导出新订单数据失败', $val);
                    $this->error('小程序完成 或 扣款 回调记录导出新订单数据失败');
                    continue;
                } else {
                    $arr = $val;
                    $arr['out_order_no'] = $val['order_no'];
                    unset($arr['order_no']);
                    $result = \App\Order\Modules\Repository\OrderMiniNotifyLogRepository::add($arr);
                    if (!$result) {
                        DB::rollBack();
                        \App\Lib\Common\LogApi::debug('小程序完成 或 扣款 回调记录导出新订单插入失败', $val);
                        $this->error('小程序完成 或 扣款 回调记录导出新订单插入失败');
                        continue;
                    }
                    $bar->advance();
                }
            }
            DB::commit();
            $bar->finish();
            $this->info('导入小程序订单数据成功');
        }catch(\Exception $e){
            DB::rollBack();
            $this->error($e->getMessage());
            die;
        }
    }
}
