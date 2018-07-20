<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class ImportHistoryFundauth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:Fundauth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $update = true;
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
        $total = \DB::connection('mysql_01')->table('zuji_payment_fund_auth')
            ->where([
                ['zuji_payment_fund_auth.auth_status', '>=', 3],
            ])->count();
        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 10;
            $page   = 1;
            $totalpage = ceil($total/$limit);

            $arr =[];

            do {

                //开启事务
                DB::beginTransaction();
                // 查询数据
                $result = \DB::connection('mysql_01')->table('zuji_payment_fund_auth')
                    ->select('zuji_payment_fund_auth.*','zuji_payment_fund_auth_notify.gmt_trans')

                    ->where([
                        ['zuji_payment_fund_auth.auth_status', '>=', 3],
                        ['zuji_payment_fund_auth_notify.operation_type', '=', 'FREEZE'],
                    ])
                    ->leftJoin('zuji_payment_fund_auth_notify', 'zuji_payment_fund_auth.fundauth_no', '=', 'zuji_payment_fund_auth_notify.request_no')
                    ->orderBy('auth_id', 'DESC')
                    ->forPage($page,$limit)
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item){

                    // 查询订单信息
                    $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_no','user_id','zujin')->where(['order_id'=>$item['order_id']])->first();
                    $orderInfo = objectToArray($orderInfo);
                    if(!$orderInfo){
                        $arr[$item['auth_id'].'order_info'] = "";
                        DB::rollBack();
                    }

                    // 用户id
                    $user_id   = $orderInfo['user_id'];
                    if(!$user_id){
                        $arr[$item['auth_id'].'user_id'] = "";
                        continue;
                    }

                    // 业务系统授权编号
                    $out_fundauth_no    = $item['fundauth_no'];
                    // 支付宝授权编号
                    $alipay_fundauth_no = $item['auth_no'];

                    // 支付系统授权编码
                    $fundauth_no = create_fundauth_no($item['create_time']);

					// 创建（支付）系统 支付宝预授权表 zuji_pay_alipay_fundauth
                    $pay_ali_fund_data = [
                        'alipay_fundauth_no'    => $alipay_fundauth_no,         // '支付宝预授权码',
                        'status'                => "AUTHORIZED",                // '状态：INIT：初始 AUTHORIZED：已授权 FINISH：完成 CLOSED：关闭',
                        'amount'                => $item['amount'],             // '授权金额',
                        'payer_user_id'         => $item['payer_user_id'],      // '用户端付款方',
                        'payer_logon_id'        => $item['payer_logon_id'],     // '用户端付款方',
                        'payee_user_id'         => $item['payee_user_id'],      // '收款方支付宝用户号',
                        'payee_logon_id'        => $item['payee_logon_id'],     // '收款方支付宝用户号',
                        'create_time'           => $item['create_time'],        // '创建时间',
                        'update_time'           => $item['update_time'],        // '修改时间',
                        'gmt_trans'             => date('Y-m-d H:i:s',$item['create_time']),          // '授权成功时间',
                    ];

                    //有记录则跳出
                    $pay_ali_fund_info = \DB::connection('pay')->table('zuji_pay_alipay_fundauth')
                        ->where([
                            ['alipay_fundauth_no', '=', $alipay_fundauth_no],
                        ])->first();

                    // 存在
                    if($pay_ali_fund_info){
                        // 使用数据库中的 授权码
                        $fundauth_no = $pay_ali_fund_info->fundauth_no;

                        // 更新
                        if( $this->update ){
                            \DB::connection('pay')->table('zuji_pay_alipay_fundauth')
                                ->where([
                                    ['alipay_fundauth_no', '=', $alipay_fundauth_no]
                                ])->update( $pay_ali_fund_data );
                        }
                    }else{ // 不存在
                        // 新增记录
                        $pay_ali_fund_data['fundauth_no'] = $fundauth_no;                // '支付系统授权编码',
                        $pay_ali_fund_id = \DB::connection('pay')->table('zuji_pay_alipay_fundauth')->insert($pay_ali_fund_data);
                        if(!$pay_ali_fund_id){
                            $arr[$item['auth_id'].'zuji_pay_alipay_fundauth'] = $pay_ali_fund_data;
                            DB::rollBack();
                            continue;
                        }
                    }

                    // 创建（支付）系统 授权表 zuji_pay_fundauth
                    $pay_fundauth_data = [
                        'app_id'                => 1,                               // '业务应用平台ID',
                        'out_fundauth_no'       => $out_fundauth_no,                // '业务平台授权编码',
                        'user_id'               => $user_id,                        // '用户id',
                        'name'                  => "旧系统资金预授权",                 // '业务平台授权名称',
                        'channel_type'          => 2,                               // '渠道 1：银联 2：支付宝',
                        'total_freeze_amount'   => $item['amount'] * 100,           // '累计冻结金额；单位：分',
                        'total_unfreeze_amount' => $item['unfreeze_amount'] * 100,  // '累计解冻金额；单位：分',
                        'total_pay_amount'      => $item['pay_amount'] * 100,       // '累计转支付金额；单位：分',
                        'rest_amount'           => ($item['amount'] - $item['unfreeze_amount'] - $item['pay_amount']) * 100,        // '订单总共剩余的冻结金额，单位为：分',
                        'status'                => 1,                               // '状态；0：未授权；1：授权成功；2：支付失败；3：超时；4：关闭（无支付）；5：完成（有支付）；6：异常',
                        'create_time'           => $item['create_time'],            // '创建时间',
                        'auth_time'             => $item['create_time'],   // '授权成功时间',
                        'update_time'           => $item['update_time'],            // '状态更新时间',

                    ];

                    //有记录则跳出
                    $pay_fundauth_info = \DB::connection('pay')->table('zuji_pay_fundauth')
                        ->where([
                            ['fundauth_no', '=', $fundauth_no]
                        ])
                        ->first();

                    // 存在
                    if($pay_fundauth_info){
                        // 更新
                        if( $this->update ){
                            \DB::connection('pay')->table('zuji_pay_fundauth')
                                ->where([
                                    ['fundauth_no', '=', $fundauth_no]
                                ])->update( $pay_fundauth_data );
                        }
                    }else{ // 不存在
                        // 新增记录
                        $pay_fundauth_data['fundauth_no'] = $fundauth_no;                // '支付系统授权编码',
                        $pay_fundauth_id = \DB::connection('pay')->table('zuji_pay_fundauth')->insert($pay_fundauth_data);
                        if(!$pay_fundauth_id){
                            $arr[$item['auth_id'].'zuji_pay_fundauth'] = $pay_fundauth_data;
                            DB::rollBack();
                            continue;
                        }
                    }


                    //--------------------------------------------------------------------------------------------------

            // 创建（订单）系统 授权表 order_pay
                    $order_pay_data = [
                        'user_id'           => $user_id,                    // '用户ID',
                        'business_type'     => 1,                           // '业务类型', 订单业务
                        'business_no'       => $item['order_no'],           // '业务编号',
                        'status'            => 4,                           // '状态：0：无效；1：待支付；2：待签代扣协议；3：预授权；4：完成；5：关闭',
                        'order_no'          => $item['order_no'],           // '订单号'

                        'payment_status'    => 2,                           // '支付-状态：0：无需支付；1：待支付；2：支付成功；3：支付失败',
                        'payment_channel'   => 2,                           // '支付-渠道'
                        'payment_amount'    => $item['amount'],             // '支付-金额；单位：元'
                        'payment_fenqi'     => 0,                           // '支付-分期数；0：不分期；取值范围[0,3,6,12]'

                        'create_time'       => $item['create_time'],        // '创建时间戳',
                        'update_time'       => $item['update_time'],        // '更新时间戳',
                        'fundauth_status'   => 2,                           // '预授权-状态：0：无需资金授权；1：待授权；2：授权成功；3：授权失败',
                        'fundauth_channel'  => 2,                           // '预授权-渠道',
                        'fundauth_amount'   => $item['amount'],             // '预授权-金额；单位：元',
                    ];

                    // 有记录则跳出
                    $order_pay_info = \App\Order\Models\OrderPayModel::query()->where(
                        ['business_type'=>1,
                        'order_no'=>$item['order_no']]
                    )->first();
                    if($order_pay_info){
                        // 更新
                        if( $this->update ){
                            \App\Order\Models\OrderPayModel::query()->where(['fundauth_no'=>$out_fundauth_no])
                                ->update( $order_pay_data );
                        }
                    }else{
                        $order_pay_data['fundauth_no'] = $out_fundauth_no;                // '支付系统授权编码',

                        $order_pay_id = \App\Order\Models\OrderPayModel::insert($order_pay_data);
                        if(!$order_pay_id){
                            $arr[$item['auth_id'].'order_pay'] = $order_pay_data;
                            DB::rollBack();
                            continue;
                        }

                    }

            // 创建（订单）系统 预授权环节明细表 order_pay_fundauth
                    $order_pay_fundauth_data = [
                        'out_fundauth_no'  => $fundauth_no,                 // '支付系统代扣协议码',
                        'fundauth_status'  => 1,                            // '状态：1：已授权；2：已关闭；3：完成',
                        'user_id'          => $user_id,                     // '用户ID',
                        'freeze_time'      => $item['create_time'],			// '冻结时间',
                        'total_amount'     => $item['amount'],              // '累计冻结金额；单位：元',
                        'unfreeze_amount'  => $item['unfreeze_amount'],     // '累计解冻金额；单位：元',
                        'pay_amount'       => $item['pay_amount'],          // '累计转支付金额；单位：元',
                    ];


                    // 有记录则跳出
                    $order_pay_fundauth_info = \App\Order\Models\OrderPayFundauthModel::query()->where(['fundauth_no'=>$out_fundauth_no])->first();
                    if($order_pay_fundauth_info){
                        // 更新
                        if( $this->update ){
                            \App\Order\Models\OrderPayFundauthModel::query()->where(['fundauth_no'=>$out_fundauth_no])
                                ->update( $order_pay_fundauth_data );
                        }
                    }else{
                        $order_pay_fundauth_data['fundauth_no'] = $out_fundauth_no;                // '支付系统授权编码',

                        $order_pay_fundauth_id = \App\Order\Models\OrderPayFundauthModel::updateOrCreate($order_pay_fundauth_data);
                        if(!$order_pay_fundauth_id){
                            $arr[$item['auth_id'].'order_pay_fundauth'] = $order_pay_fundauth_data;
                            DB::rollBack();
                            continue;
                        }

                    }
					
					$bar->advance();

                }
                // 提交事务
                DB::commit();

                $page++;
            } while ($page <= $totalpage);

            if(count($arr)>0){
                LogApi::notify("资金预授权导表错误",$arr);
            }

            $bar->finish();
            echo "导入成功";die;

        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }







}
