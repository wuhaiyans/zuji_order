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
        //小程序回调数据表
        DB::beginTransaction();
        $total = \DB::connection('mysql_02')->table('zuji_order2')->whereIn("appid",$appid)
            ->count();
        $bar = $this->output->createProgressBar($total);
        try {
            $old_orders = \DB::connection('mysql_02')->table('zuji_order2')->whereIn("appid",$appid)->select('*')->get();
            $old_orders = objectToArray($old_orders);
            foreach ($old_orders as $key => $val) {
                $miniOrderCreditPayArr = [];
                //查询当前订单是否存在芝麻订单号
                $old_mini_orders = \DB::connection('mysql_02')->table('zuji_zhima_certification')->where(['out_order_no'=>$val['order_no']])->select('order_no')->get();
                $old_mini_orders = objectToArray($old_mini_orders);
                if(empty($old_mini_orders)){
                    \App\Lib\Common\LogApi::debug('小程序认证订单查询zuji_zhima_certification订单不存在', $val);
                    $this->error('小程序认证订单查询zuji_zhima_certification订单不存在');
                    continue;
                }
                //查询分期数据
                $old_orders_instalment = \DB::connection('mysql_02')->table('zuji_order2_instalment')->where(['order_id'=>$val['order_id']])->select('*')->get();
                $old_orders_instalment = objectToArray($old_orders_instalment);
                if(empty($old_orders_instalment)){
                    \App\Lib\Common\LogApi::debug('小程序认证订单查询zuji_order2_instalment订单不存在', $val);
                    $this->error('小程序认证订单查询zuji_order2_instalment订单不存在');
                    continue;
                }
                foreach($old_orders_instalment as $k=>$v){
                    //当请求交易码存在的时候（并且扣款状态为未扣款或扣款失败情况）将记录保存
                    if( $v['status'] != '1' || $v['status'] != '3' ){
                        if ( $v['trade_no'] != '' ){
                            $miniOrderCreditPayArr['out_trans_no'] = $v['trade_no'];//请求流水号
                            $miniOrderCreditPayArr['order_operate_type'] = 'INSTALLMENT';//请求类型
                            $miniOrderCreditPayArr['out_order_no'] = $val['order_no'];//商户订单号
                            $miniOrderCreditPayArr['zm_order_no'] = $old_mini_orders[0]['order_no'];//芝麻订单号
                            $miniOrderCreditPayArr['remark'] = $val['order_no'];//备注
                            $miniOrderCreditPayArr['pay_amount'] = $v['amount']/100;//请求金额
                            $result = \App\Order\Modules\Repository\OrderMiniCreditPayRepository::add( $miniOrderCreditPayArr );
                            if (!$result) {
                                DB::rollBack();
                                \App\Lib\Common\LogApi::debug( '小程序完成 或 扣款 回调记录导出新订单插入失败', $miniOrderCreditPayArr );
                                $this->error('小程序完成 或 扣款 回调记录导出新订单插入失败');
                                continue;
                            }
                            $bar->advance();
                        }
                    }
                }
            }
            DB::commit();
            $bar->finish();
            $this->info('导入小程序订单数据成功');
        }catch(\Exception $e){
            DB::rollBack();
            \App\Lib\Common\LogApi::debug('小程序请求数据导入异常', $e->getMessage());
            $this->error($e->getMessage());
            die;
        }
    }
}
