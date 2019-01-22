<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOtherUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportOtherUser';

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
        //用户数据
        $qudian = include(__DIR__."/import_alipay_member/qudian_apple.php");
        $this->exc($qudian['user_list'],$qudian['user_from']);

        $qudian = include(__DIR__."/import_alipay_member/qudian_vivo.php");
        $this->exc($qudian['user_list'],$qudian['user_from']);

        $qudian = include(__DIR__."/import_alipay_member/tongchengbang.php");
        $this->exc($qudian['user_list'],$qudian['user_from']);

    }
    public function exc($list,$third_user){
        $bar = $this->output->createProgressBar(count($list));
        try{

            foreach ($list as $k=>$v) {

                //解析参数
                $ali_user_id = $v[0];
                $realname = $v[1];
                $mobile = $v[2];
                $province = $v[3];
                $city = $v[4];

                //设置业务流程走向条件 1：更新用户;2：更新用户并新增阿里用户信息;3：新增用户并新增阿里用户信息
                $condition = "";
                $whereArr= [
                    ['user_id','=',$ali_user_id]
                ];

                //查询支付宝信息
                $alipayUser = \DB::connection('mysql_01')->table('zuji_member_alipay')->where($whereArr)->first();

                if($alipayUser){
                    $condition = 1;
                }
                else{
                    $condition = 2;
                }
                //查询用户信息
                $where = [
                    ['username','=',$mobile]
                ];
                $user = \DB::connection('mysql_01')->table('zuji_member')->where($where)->first();

                if($user){
                    $condition = $condition==1?1:2;
                }
                else{
                    $condition = 3;
                }

                //支付宝数据
                $ali_data = [
                    'user_id'=>$ali_user_id,
                    'province'=>$province,
                    'city'=>$city,
                    'nick_name'=>$realname,
                ];

                switch($condition){
                    //更新用户
                    case 1:
                        $ret = $this->user_save($mobile,$third_user);
                        break;
                    //更新用户并新增阿里用户信息
                    case 2:
                        $this->user_save($mobile,$third_user);
                        $ali_data['member_id'] = $user->id;
                        $ret = $this->alipay_add($ali_data);
                        break;
                    //新增用户并新增阿里用户信息
                    case 3:
                        //用户数据
                        $user_data = [
                            'username'=>$mobile,
                            'mobile'=>$mobile,
                            'third_user'=>$third_user,
                            'realname'=>$realname,
                            'register_time'=>time(),
                        ];
                        $ret = $this->user_add($user_data);
                        if($ret){
                            $ali_data['member_id'] = $ret;
                            $ret = $this->alipay_add($ali_data);
                        }
                        break;
                }

                if(!$ret){
                    $arr[$k] = $mobile;
                }
                $bar->advance();
            }
            $bar->finish();
            if(count($arr)>0){
                LogApi::notify("ImportOtherUser:第三方用户数据导入失败",$arr);
                echo "部分导入成功";
                return;
            }
            echo "导入成功";
            return;
        }catch (\Exception $e){
            echo $e->getMessage();
            return;
        }
    }
    //更新用户
    public function user_save($mobile,$third_user){
        $data = [
            'third_user'=>$third_user
        ];
        $ret =  \DB::connection('mysql_01')->table('zuji_member')->where(['mobile'=>$mobile])->update($data);
        return $ret;
    }
    //注册用户
    public function user_add($data){
        $ret =  \DB::connection('mysql_01')->table('zuji_member')->insertGetId($data);
        return $ret;
    }
    //注册支付宝信息
    public function alipay_add($data){
        $ret =  \DB::connection('mysql_01')->table('zuji_member_alipay')->insert($data);
        return $ret;
    }
}
