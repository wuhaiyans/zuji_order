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

class ImportHistoryOrderMiniInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryOrderMiniInfo';

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
        $total = \DB::connection('mysql_01')->table('zuji_zhima_certification')
            ->count();
        $i = 0;
        $bar = $this->output->createProgressBar($total);
        try {
            set_time_limit(0);//0表示不限时
            DB::beginTransaction();
            $old_mini_orders = \DB::connection('mysql_01')->table('zuji_zhima_certification')->get();
            $old_mini_orders = objectToArray($old_mini_orders);
            foreach($old_mini_orders as $key=>$val){
                $miniOrderInfoArr = [];
                $old_order2 = \DB::connection('mysql_01')->table('zuji_order2')->where(['order_no'=>$val['out_order_no']])->first();
                $old_order2 = objectToArray($old_order2);
                if(empty($old_order2)){
                    $i++;
                    continue;
                }
                if(config('miniappid.'.$old_order2['appid'])){
                    $miniOrderInfoArr['appid'] = config('miniappid.'.$old_order2['appid']);//芝麻小程序appid
                }else{
                    $i++;
                    continue;
                }
                if( $old_order2['zuqi_type'] == 2 ){//租期类型（1：天；2：月）
                    $overdue_time = date('Y-m-d H:i:s', strtotime($val['create_time'].' +'.(intval($old_order2['zuqi'])+1).' month'));
                }else{
                    $overdue_time = date('Y-m-d H:i:s', strtotime($val['create_time'].' +'.(intval($old_order2['zuqi'])+30).' day'));
                }
                $miniOrderInfoArr['overdue_time'] = $overdue_time;//订单逾期时间
                $miniOrderInfoArr['out_order_no'] = $val['out_order_no'];//商户端订单号
                $miniOrderInfoArr['order_no'] = $val['order_no'];//芝麻订单号
                $miniOrderInfoArr['transaction_id'] = $val['trade_no'];//芝麻请求流水号
                $miniOrderInfoArr['name'] = $val['name'];//用户姓名
                $miniOrderInfoArr['cert_no'] = $val['cert_no'];//身份证号
                $miniOrderInfoArr['mobile'] = $val['mobile'];//手机号
                $miniOrderInfoArr['house'] = $val['house'];//用户地址
                $miniOrderInfoArr['zm_grade'] = $val['zm_grade'];//信用级别
                $miniOrderInfoArr['credit_amount'] = $val['credit_amount'];//信用权益金额
                if( $val['zm_risk'] == 1 ){
                    $miniOrderInfoArr['zm_risk'] = 'Y';//芝麻风控产品集联合结果
                }else{
                    $miniOrderInfoArr['zm_risk'] = 'N';//芝麻风控产品集联合结果
                }
                if( $val['zm_face'] == 1 ){
                    $miniOrderInfoArr['zm_face'] = 'Y';//人脸核身结果
                }else{
                    $miniOrderInfoArr['zm_face'] = 'N';//人脸核身结果
                }
                $miniOrderInfoArr['create_time'] = $val['create_time'];//订单查询时间
                $miniOrderInfoArr['user_id'] = $val['user_id'];//支付宝 userid
                $miniOrderInfoArr['channel_id'] = $val['channel_id'];//渠道来源
                if(strlen($val['trade_no']) < 1){
                    $i++;
                    continue;
                }else{
                    $result = \App\Order\Modules\Repository\OrderMiniRepository::add($miniOrderInfoArr);
                    if( !$result ){
                        DB::rollBack();
                        $i++;
                    }
                }
                $bar->advance();
            }
            DB::commit();
            $bar->finish();
            echo '失败次数'.$i;
            $this->info('导入小程序订单数据成功');
        }catch(\Exception $e){
            DB::rollBack();
            \App\Lib\Common\LogApi::debug('小程序订单数据导入异常', $e->getMessage());
            $this->error($e->getMessage());
            die;
        }
    }







}
