<?php
/**
 *小程序认证订单数据
 *
 *将旧订单系统小程序表数据导入到新订单系统
 * @author      zhangjinhui<15116906320@163.com>
 * @since        1.0
 */
namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoryMiniCertification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryMiniCertification';

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
        //小程序查询数据表
        $total = \DB::connection('mysql_01')->table('zuji_zhima_certification')
            ->count();
        $bar = $this->output->createProgressBar($total);
        try {
            set_time_limit(0);//0表示不限时
            DB::beginTransaction();
            $old_mini_orders = \DB::connection('mysql_01')->table('zuji_zhima_certification')->select('*')->get();
            $old_mini_orders = objectToArray($old_mini_orders);
            foreach($old_mini_orders as $key=>$val){
                $old_order2 = \DB::connection('mysql_01')->table('zuji_order2')->select('*')->where(['order_no'=>$val['out_order_no']])->limit(5)->get();
                $old_order2 = objectToArray($old_order2);
                if(empty($old_order2)){
                    \App\Lib\Common\LogApi::debug('小程序认证订单查询order2订单不存在', $val);
                    $this->error('小程序认证订单查询order2订单不存在');
                    continue;
                }
                if(config('miniappid.'.$old_order2[0]['appid'])){
                    $val['appid'] = config('miniappid.'.$old_order2[0]['appid']);
                }else{
                    \App\Lib\Common\LogApi::debug('小程序appid匹配失败', $val);
                    $this->error('小程序appid匹配失败');
                    continue;
                }
                unset($val['open_id']);
                unset($val['zm_score']);
                if( $old_order2[0]['zuqi_type'] == 2 ){//租期类型（1：天；2：月）
                    $overdue_time = date('Y-m-d H:i:s', strtotime($val['create_time'].' +'.(intval($old_order2[0]['zuqi'])+1).' month'));
                }else{
                    $overdue_time = date('Y-m-d H:i:s', strtotime($val['create_time'].' +'.(intval($old_order2[0]['zuqi'])+30).' day'));
                }
                $val['overdue_time'] = $overdue_time;
                $val['create_time'] = strtotime($val['create_time']);
                $val['transaction_id'] = $val['trade_no'];
                $val['zm_order_no'] = $val['order_no'];
                $val['order_no'] = $val['out_order_no'];
                if(strlen($val['trade_no']) < 1){
                    \App\Lib\Common\LogApi::debug('小程序trade_no错误', $val);
                    $this->error('小程序trade_no错误');
                    continue;
                }else{
                    $result = \App\Order\Modules\Repository\OrderMiniRepository::add($val);
                    if( !$result ){
                        DB::rollBack();
                        \App\Lib\Common\LogApi::debug('小程序认证记录导入失败',$val);
                        $this->error('小程序认证记录导入失败');
                    }
                }
                $bar->advance();
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
