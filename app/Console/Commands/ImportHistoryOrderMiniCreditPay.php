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

class ImportHistoryOrderMiniCreditPay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryOrderMiniCreditPay';

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
        $appid =[
            36,92,91,90,130,
        ];
        $i = 0;
        //小程序回调数据表
        DB::beginTransaction();
        $total = \DB::connection('mysql_01')->table('zuji_order2')->whereIn("appid",$appid)
            ->count();
        $bar = $this->output->createProgressBar($total);
        try {
            $old_orders = \DB::connection('mysql_01')->table('zuji_order2')->whereIn("appid",$appid)->select('*')->get();
            $old_orders = objectToArray($old_orders);
            foreach ($old_orders as $key => $val){
                $miniOrderCreditPayArr = [];
                //查询当前订单是否存在芝麻订单号
                $old_mini_orders = \DB::connection('mysql_01')->table('zuji_zhima_certification')->where(['out_order_no'=>$val['order_no']])->first();
                $old_mini_orders = objectToArray($old_mini_orders);
                if(empty($old_mini_orders)){
                    $i++;
                    continue;
                }
                //查询分期数据
                $old_orders_instalment = \DB::connection('mysql_01')->table('zuji_order2_instalment')->where(['order_id'=>$val['order_id']])->get();
                $old_orders_instalment = objectToArray($old_orders_instalment);
                if(empty($old_orders_instalment)){
                    $i++;
                    continue;
                }
                foreach($old_orders_instalment as $k=>$v){
                    //当请求交易码存在的时候（并且扣款状态为未扣款或扣款失败情况）将记录保存
                    if( $v['status'] == '1' || $v['status'] == '2' || $v['status'] == '3' ){
                        if ( $v['trade_no'] != '' ){
                            $miniOrderCreditPayArr['out_trans_no'] = $v['trade_no'];//请求流水号
                            $miniOrderCreditPayArr['order_operate_type'] = 'INSTALLMENT';//请求类型
                            $miniOrderCreditPayArr['out_order_no'] = $val['order_no'];//商户订单号
                            $miniOrderCreditPayArr['zm_order_no'] = $old_mini_orders['order_no'];//芝麻订单号
                            $miniOrderCreditPayArr['remark'] = $val['order_no'];//备注
                            $miniOrderCreditPayArr['pay_amount'] = $v['amount']/100;//请求金额
                            $result = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::add( $miniOrderCreditPayArr );
                            if (!$result) {
                                $i++;
                                DB::rollBack();
                                continue;
                            }
                            $bar->advance();
                        }else{
                            $i++;
                        }
                    }else{
                        $i++;
                    }
                }
            }
            DB::commit();
            $bar->finish();
            echo '失败次数'.$i;
            $this->info('导入小程序订单数据成功');
        }catch(\Exception $e){
            DB::rollBack();
            $this->error($e->getMessage());
            die;
        }
    }
}
