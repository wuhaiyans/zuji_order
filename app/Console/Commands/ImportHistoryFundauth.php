<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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


        $result = \DB::connection('mysql_01')->table('zuji_payment_fund_auth')->select('*')->orderBy('auth_id', 'DESC')->offset(0)->limit(2)->get()->toArray();
        $result = objectToArray($result);
//        p($result);


        foreach($result as &$item){
            // 创建支付系统 支付宝预授权表 zuji_pay_alipay_fundauth
            $pay_ali_fund_data = [
                'fundauth_no'           => create,        // '支付系统授权编码',
                'alipay_fundauth_no'    => $item['auth_no'],            // '支付宝预授权码',
                'status'                => $item['status'],             // '状态：INIT：初始 AUTHORIZED：已授权 FINISH：完成 CLOSED：关闭',
                'amount'                => $item['amount'],             // '授权金额',
                'payer_user_id'         => $item['payer_user_id'],      // '用户端付款方',
                'payer_logon_id'        => $item['payer_logon_id'],     // '用户端付款方',
                'payee_user_id'         => $item['payee_user_id'],      // '收款方支付宝用户号',
                'payee_logon_id'        => $item['payee_logon_id'],     // '收款方支付宝用户号',
                'create_time'           => $item['create_time'],        // '创建时间',
                'update_time'           => $item['update_time'],        // '修改时间',
                'gmt_trans'             => $item['gmt_trans'],          // '授权成功时间',
            ];


            p($pay_ali_fund_data);
            $pay_ali_fund_id = \DB::connection('pay')->table('zuji_pay_alipay_fundauth')->insert($pay_ali_fund_data);
            p($pay_ali_fund_id);


            $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_no','user_id','zujin')->where(['order_id'=>$item['order_id']])->first();
            $orderInfo = objectToArray($orderInfo);


            $status = [
                0   => 0,
                1   => 0,
                2   => 0,
                3   => 1,
                4   => 3,
                5   => 2,
            ];
            $fundauth['fundauth_no']                = $item['order_no'];                //'代扣协议码',
            $fundauth['out_fundauth_no']            = $item['create_time'];             //'支付系统代扣协议码',

            $fundauth['fundauth_status']            = $status[$item['auth_status']];    //'状态：1：已授权；2：已关闭；3：完成',
            $fundauth['user_id']                    = $orderInfo['user_id'];            //'用户ID',
            $fundauth['freeze_time']                = $item['create_time'];             //'冻结时间',
            $fundauth['unfreeze_time']              = $item['update_time'];             //'解冻时间',
            $fundauth['total_amount']               = $item['amount'];                  //'累计冻结金额',
            $fundauth['unfreeze_amount']            = $item['unfreeze_amount'];         //'累计解冻金额',
            $fundauth['pay_amount']                 = $item['pay_amount'];              //'累计转支付金额',

            p($item);
            // 插入数据
            \App\Order\Models\OrderGoodsInstalment::create($item);

        }

    }
}
