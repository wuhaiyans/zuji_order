<?php
/**
 *小程序用户授权信息接口
 *
 *将旧订单系统小程序表数据导入到新订单系统
 * @author      zhangjinhui<15116906320@163.com>
 * @since        1.0
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoryMemberAlipay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportHistoryMemberAlipay';

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
        //小程序认证数据
        DB::beginTransaction();
        $total = \DB::connection('mysql_01')->table('zuji_member_alipay')->count();
        $bar = $this->output->createProgressBar($total);
        $i = 0;
        try {
            $old_member_alipay = \DB::connection('mysql_01')->table('zuji_member_alipay')->get();
            $old_member_alipay = objectToArray($old_member_alipay);
            foreach ($old_member_alipay as $key => $val) {
                //查询认证数据
                $old_member = \DB::connection('mysql_01')->table('zuji_member')->where(['id'=>$val['member_id']])->first();
                $old_member = objectToArray($old_member);
                if(empty($old_member)){
                    $i++;
                    continue;
                }
                //查询阿里云系统用户订单号
                $new_member = \DB::connection('mysql_02')->table('zuji_member')->where(['mobile'=>$old_member['mobile']])->first();
                $new_member = objectToArray($new_member);
                if(empty($new_member)){
                    $i++;
                    continue;
                }
                //入库
                $zuji_member_alipay = [
                    'member_id'=>$new_member['id'],
                    'user_id'=>$val['user_id'],
                    'province'=>$val['province'],
                    'city'=>$val['city'],
                    'nick_name'=>$val['nick_name'],
                    'is_student_certified'=>$val['is_student_certified'],
                    'user_type'=>$val['user_type'],
                    'user_status'=>$val['user_status'],
                    'is_certified'=>$val['is_certified'],
                    'gender'=>$val['gender'],
                ];
                $result = \DB::connection('mysql_02')->table('zuji_member_alipay')->insert($zuji_member_alipay);
                if (!$result) {
                    DB::rollBack();
                    $i++;
                    continue;
                }
            }
            DB::commit();
            $bar->finish();
            echo '失败次数'.$i;
            $this->info('导入小程序认证信息成功');
        }catch(\Exception $e){
            DB::rollBack();
            \App\Lib\Common\LogApi::debug('小程序请求数据导入异常', $e->getMessage());
            $this->error($e->getMessage());
            die;
        }
    }
}
