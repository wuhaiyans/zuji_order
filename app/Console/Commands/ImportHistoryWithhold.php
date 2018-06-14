<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoryWithhold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:Withhold';

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

        $total = \DB::connection('mysql_01')->table('zuji_withholding_alipay')->count();
        try{
            $limit  = 10;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            do {

                // 查询数据
                $result = \DB::connection('mysql_01')->table('zuji_withholding_alipay')
                    ->select('zuji_withholding_alipay.*','zuji_withholding_notify_alipay.scene')
                    // 查询 已签约的数据
                    ->where(['zuji_withholding_alipay.status'=>1,'zuji_withholding_notify_alipay.status'=>'NORMAL'])
                    ->leftJoin('zuji_withholding_notify_alipay', 'zuji_withholding_alipay.agreement_no', '=', 'zuji_withholding_notify_alipay.agreement_no')
                    ->groupBy('zuji_withholding_alipay.agreement_no')
                    ->orderBy('zuji_withholding_alipay.id', 'DESC')
                    ->forPage($page,$limit)
                    ->get()->toArray();
                $result = objectToArray($result);
//                p($result);



                foreach($result as &$item){

                    // 开启事务
                    DB::beginTransaction();

                    // 代扣协议号
                    $agreement_no        = createNo(10);

                    // 支付宝授权编号
                    $alipay_agreement_no = $item['agreement_no'];

                    $user_id = $item['user_id'];
                    /*
                       [id] => 426
                       [user_id] => 3                           // 租机用户ID
                       [partner_id] => 4294967295               // 合作者身份 ID ；商户签约的支付宝账号对应的支付宝唯一用户号
                       [alipay_user_id] => 2088922681574031     // 支付宝用号：用户签约的支付宝账号对应的支付宝唯一用户号
                       [agreement_no] => 20185413440169491003   // 支付宝代扣协议号
                       [status] => 2                            // 协议状态：0：无效记录；1：已签约：2：已解约
                       [sign_time] => 2018-06-13 19:31:49       // 签约时间；支付宝代扣协议的实际签约时间，格式为
                       [valid_time] => 2018-06-13 19:31:49      // 协议生效时间
                       [invalid_time] => 2115-02-01 00:00:00    // 协议失效时间
                       [unsign_time] => 2018-06-13 19:34:11     // 解约时间；如果协议未修改过，本参数值等于签约时间
                       [scene] => DEFAULT|DEFAULT
                   */
//                    p($item);

            // 创建（支付）系统 代扣 表 zuji_pay_alipay_withhold
                    $zuji_pay_alipay_data = [
                        'agreement_no'          => $agreement_no,               // '代扣协议号',
                        'alipay_agreement_no'   => $alipay_agreement_no,        // '支付宝代扣协议号',
                        'partner_id'            => $item['partner_id'],         // '合作商ID',
                        'status'                => 'NORMAL',                    // '状态：TEMP：初始；NORMAL：已签约；UNSIGN：已解约',
                        'alipay_user_id'        => $item['alipay_user_id'],      // '支付宝唯一用户号，以 2088开头的 16位纯数字',
                        'sign_time'             => $item['sign_time'],          // '签约时间',
                        'valid_time'            => $item['valid_time'],         // '签约生效时间',
                        'invalid_time'          => $item['invalid_time'],       // '签约失效时间',

                        'scene'                 => $item['scene'],              // '签约场景',
                        'external_user_id'      => $agreement_no,               // '商户用户标识（业务平台ID-业务平台用户ID-支付系统协议号）',

                        'sign_modify_time'      => $item['sign_time'],          // '最近一次协议修改时间，如果协议未修改过，本参数值等于签约时间',
                    ];
//                    p($zuji_pay_alipay_data);
//                    sql_profiler();
                    $zuji_pay_alipay_id = \DB::connection('pay')->table('zuji_pay_alipay_withhold')->insert($zuji_pay_alipay_data);
                    if(!$zuji_pay_alipay_id){
                        DB::rollBack();
                    }
//                    die;


            // 创建（支付）系统 代扣 表 zuji_pay_withhold
                    $zuji_pay_withhold_data = [
                        'agreement_no'      => $agreement_no,                   // '代扣协议号',
                        'app_id'            => 1,                               // '应用渠道ID',
                        'user_id'           => $user_id,                        // '用户ID',
                        'out_agreement_no'  => $alipay_agreement_no,            // '代扣协议号',
                        'channel_type'      => 2,                               // '渠道类型：1：银联；2：支付宝',
                        'status'            => 1,                               // '状态：0：创建；1：已签约；2：已解约',
                        'sign_time'         => strtotime($item['sign_time']),   // '签约时间戳',
                    ];

                    $zuji_pay_withhold_id = \DB::connection('pay')->table('zuji_pay_withhold')->insert($zuji_pay_withhold_data);
                    if(!$zuji_pay_withhold_id){
                        DB::rollBack();
                    }

                    //--------------------------------------------------------------------------------------------------

                    // 查询

            // 创建（订单）系统 授权表 order_pay
                    $order_pay_data = [
                        'user_id'           => $user_id,                    // '用户ID',
                        'business_type'     => 1,                           // '业务类型', 订单业务
                        'business_no'       => $agreement_no,               // '业务编号',
                        'status'            => 4,                           // '状态：0：无效；1：待支付；2：待签代扣协议；3：预授权；4：完成；5：关闭',

                        'withhold_status'   => 2,                           // '代扣协议-状态：0：无需签约；1：待签约；2：签约成功；3：签约失败',
                        'withhold_channel'  => 2,                           // '代扣协议-渠道',
                        'withhold_no'       => $alipay_agreement_no,        // '代扣协议-编号',
                    ];
                    $order_pay_id = \App\Order\Models\OrderPayModel::create($order_pay_data);
                    if(!$order_pay_id){
                        DB::rollBack();
                    }


            // 创建（订单）系统 预授权环节明细表 order_pay_withhold
                    $order_pay_withhold_data = [
                        'withhold_no'       => $alipay_agreement_no,        // '代扣协议码',
                        'out_withhold_no'   => $agreement_no,               // '支付系统代扣协议码',
                        'withhold_channel'  => 2,                           // '支付渠道（冗余，方便以后查询）',
                        'withhold_status'   => 2,                           // '状态：0：无效前期；1：待签约；2：已签约；3：解约中；4：已解约',
                        'user_id'           => $user_id,                    // '用户ID',
                        'counter'           => 1,                           // '代扣协议使用计数；为0时才允许解除代扣；创建时默认为1',
                    ];

                    $order_pay_withhold_id = \App\Order\Models\OrderPayWithholdModel::create($order_pay_withhold_data);
                    if(!$order_pay_withhold_id){
                        DB::rollBack();
                    }
                    DB::commit();
                }
            } while ($page <= $totalpage);
            echo "导入成功";die;

        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }







}
