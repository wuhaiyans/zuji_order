<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoryWithhold extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'command:Withhold  {--begin_time=}  {--end_time=}';

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
		$begin_time = strtotime($this->option('begin_time'));
		$end_time = strtotime($this->option('end_time'));
		if( $begin_time >= $end_time ){
			echo "时间错误\n";exit;
		}
		

        $total = \DB::connection('mysql_01')->table('zuji_withholding_alipay')
            ->join('zuji_member',function ($join) {
                $join->on('zuji_member.id', '=', 'zuji_withholding_alipay.user_id')
                    ->on('zuji_withholding_alipay.agreement_no', '=', 'zuji_member.withholding_no');
            })
            ->where([
                ['zuji_withholding_alipay.status', '=', 1],
				['zuji_withholding_alipay.sign_time', '>=', date('Y-m-d H:i:s',$begin_time)],
				['zuji_withholding_alipay.sign_time', '<', date('Y-m-d H:i:s',$end_time)],
            ])
            ->count('zuji_withholding_alipay.id');

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 500;
            $page   = 1;
            $totalpage = ceil($total/$limit);
			$arr = [];
            do {
                // 查询代扣数据（已签约的，不考虑未签约数据）
                $result = \DB::connection('mysql_01')->table('zuji_withholding_alipay')
                    ->select('zuji_withholding_alipay.*','zuji_withholding_notify_alipay.scene')
                    ->join('zuji_member',function ($join) {
                        $join->on('zuji_member.id', '=', 'zuji_withholding_alipay.user_id')
                            ->on('zuji_withholding_alipay.agreement_no', '=', 'zuji_member.withholding_no');
                    })
                    ->leftJoin('zuji_withholding_notify_alipay', 'zuji_withholding_alipay.agreement_no', '=', 'zuji_withholding_notify_alipay.agreement_no')
                    ->where([
                        ['zuji_withholding_alipay.status', '=', 1],
                        ['zuji_withholding_notify_alipay.status', '=', 'NORMAL'],
						['zuji_withholding_alipay.sign_time', '>=', date('Y-m-d H:i:s',$begin_time)],
						['zuji_withholding_alipay.sign_time', '<', date('Y-m-d H:i:s',$end_time)],
                    ])

                    ->groupBy('zuji_withholding_alipay.agreement_no')
                    ->orderBy('zuji_withholding_alipay.id', 'DESC')
                    ->forPage($page,$limit)
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item){
					// 更新进度条
					$bar->advance();
					
					// 查询用户最后一个订单
					$order_info = \DB::connection('mysql_01')->table('zuji_order2')
							->where([
								['user_id', '=', $item['user_id']],
							])
							->orderBy('create_time','DESC')
							->limit(1)
							->first();
					if( !$order_info ){
						continue;
					}

                    // 支付系统代扣协议号
                    $agreement_no           = create_withhold_no($order_info->create_time);
					
                    // 订单系统代扣协议号
                    $out_agreement_no       = create_no('W',$order_info->create_time);

                    // 支付宝授权编号
                    $alipay_agreement_no    = $item['agreement_no'];
					
                    // 用户ID
                    $user_id                = $item['user_id'];


            // 创建（支付）系统 支付宝代扣 表 zuji_pay_alipay_withhold
                    $zuji_pay_alipay_data = [
                        'agreement_no'          => $agreement_no,               // '代扣协议号',
                        'alipay_agreement_no'   => $alipay_agreement_no,        // '支付宝代扣协议号',
                        'partner_id'            => '2088821542502025',         // '合作商ID',
                        'status'                => 'NORMAL',                    // '状态：TEMP：初始；NORMAL：已签约；UNSIGN：已解约',
                        'alipay_user_id'        => $item['alipay_user_id'],     // '支付宝唯一用户号，以 2088开头的 16位纯数字',
                        'sign_time'             => $item['sign_time'],          // '签约时间',
                        'valid_time'            => $item['valid_time'],         // '签约生效时间',
                        'invalid_time'          => $item['invalid_time'],       // '签约失效时间',
                        'scene'                 => $item['scene'],              // '签约场景',
                        'external_user_id'      => $agreement_no,               // '商户用户标识（业务平台ID-业务平台用户ID-支付系统协议号）',
                        'sign_modify_time'      => $item['sign_time'],          // '最近一次协议修改时间，如果协议未修改过，本参数值等于签约时间',
                    ];

                    //没有记录则添加
                    $zuji_pay_alipay_info = \DB::connection('pay')->table('zuji_pay_alipay_withhold')
                        ->where([
                            ['alipay_agreement_no', '=', $alipay_agreement_no],
                        ])->first();
                    if(!$zuji_pay_alipay_info){
                        $zuji_pay_alipay_id = \DB::connection('pay')->table('zuji_pay_alipay_withhold')->insert($zuji_pay_alipay_data);
                        if(!$zuji_pay_alipay_id){
                            $arr[$item['withhold_id'].'zuji_pay_alipay_withhold'] = $zuji_pay_alipay_data;
                        }
					}else{// 存在，还原 支付系统的代扣协议码
						$agreement_no = $zuji_pay_alipay_info->agreement_no;
						unset($zuji_pay_alipay_data['agreement_no']);
						// 更新
						\DB::connection('pay')->table('zuji_pay_alipay_withhold')
								->where([
									'agreement_no' => $agreement_no,
								])
								->limit(1)
								->update($zuji_pay_alipay_data);
					}

            // 创建（支付）系统 代扣 表 zuji_pay_withhold
                    $zuji_pay_withhold_data = [
                        'agreement_no'      => $agreement_no,                   // '代扣协议号',
                        'app_id'            => 1,                               // '应用渠道ID',
                        'user_id'           => $user_id,                        // '用户ID',
                        'out_agreement_no'  => $out_agreement_no,				// '业务系统代扣协议号',
                        'channel_type'      => 2,                               // '渠道类型：1：银联；2：支付宝',
                        'status'            => 1,                               // '状态：0：创建；1：已签约；2：已解约',
                        'sign_time'         => strtotime($item['sign_time']),   // '签约时间戳',
						'create_time'		=> strtotime($item['sign_time']) ,	// 签约时间当做创建时间
                    ];

                    //没有记录则添加
                    $zuji_pay_withhold_info = \DB::connection('pay')->table('zuji_pay_withhold')
                        ->where([
                            ['agreement_no', '=', $agreement_no],
                        ])->first();
                    if(!$zuji_pay_withhold_info) {
                        $zuji_pay_withhold_id = \DB::connection('pay')->table('zuji_pay_withhold')->insert($zuji_pay_withhold_data);
                        if (!$zuji_pay_withhold_id) {
                            $arr[$item['withhold_id'] . 'zuji_pay_withhold'] = $zuji_pay_withhold_data;
                        }
					}else{// 存在，还原 业务系统代扣协议码
						$out_agreement_no = $zuji_pay_withhold_info->out_agreement_no;
						unset($zuji_pay_withhold_data['agreement_no']);
						unset($zuji_pay_withhold_data['out_agreement_no']);
						// 更新
						\DB::connection('pay')->table('zuji_pay_withhold')
								->where([
									'agreement_no' => $agreement_no,
								])
								->limit(1)
								->update($zuji_pay_withhold_data);
					}

                    //--------------------------------------------------------------------------------------------------

                    // 查询

            // 创建（订单）系统 授权表 order_pay
                    $order_pay_data = [
                        'user_id'           => $user_id,                    // '用户ID',
                        'business_type'     => 1,                           // '业务类型', 订单业务
                        'business_no'       => $order_info->order_no,     // '业务编号',
                        'order_no'			=> $order_info->order_no,		// 订单号
                        'status'            => 4,                           // '状态：0：无效；1：待支付；2：待签代扣协议；3：预授权；4：完成；5：关闭',

                        'payment_status'    => 2,                           // '支付-状态：0：无需支付；1：待支付；2：支付成功；3：支付失败',
                        'payment_channel'   => 2,                           // '支付-渠道'
                        'payment_fenqi'     => 0,                           // '支付-分期数；0：不分期；取值范围[0,3,6,12]'

                        'withhold_status'   => 2,                           // '代扣协议-状态：0：无需签约；1：待签约；2：签约成功；3：签约失败',
                        'withhold_channel'  => 2,                           // '代扣协议-渠道',
                        'withhold_no'       => $out_agreement_no,			// '业务系统代扣协议-编号',
						'create_time'		=> strtotime($item['sign_time']) ,	// 签约时间当做创建时间
                    ];

                    //没有记录则添加
                    $order_pay_info = \App\Order\Models\OrderPayModel::query()
                        ->where([
                            ['business_type', '=', 1],
                            ['business_no', '=', $order_info->order_no],
                        ])->first();
                    if(!$order_pay_info){
                        $order_pay_id = \App\Order\Models\OrderPayModel::updateOrCreate($order_pay_data);
                        if(!$order_pay_id->getQueueableId()){
                            $arr[$item['withhold_id'].'order_pay'] = $order_pay_data;
                        }
					}else{
						\App\Order\Models\OrderPayModel::where([
									['withhold_no', '=', $out_agreement_no],
								])
								->limit(1)
								->update( $order_pay_data );
					}
					
            // 创建（订单）系统 代扣签约环节明细表 order_pay_withhold
                    $order_pay_withhold_data = [
                        'withhold_no'       => $out_agreement_no,        // '代扣协议码',
                        'out_withhold_no'   => $agreement_no,               // '支付系统代扣协议码',
                        'withhold_channel'  => 2,                           // '支付渠道（冗余，方便以后查询）',
                        'withhold_status'   => 2,                           // '状态：0：无效前期；1：待签约；2：已签约；3：解约中；4：已解约',
                        'user_id'           => $user_id,                    // '用户ID',
                        'counter'           => 1,                           // '代扣协议使用计数；为0时才允许解除代扣；创建时默认为1',
                    ];
					if( $order_info->order_status != '1' ){// 无效订单时，写入
						$order_pay_withhold_data['counter'] = 0;
					}
                    //没有记录则添加
                    $order_pay_withhold_info = \App\Order\Models\OrderPayWithholdModel::query()
                        ->where([
                            ['withhold_no', '=', $out_agreement_no],
                        ])->first();

                    if(!$order_pay_withhold_info) {
                        $order_pay_withhold_id = \App\Order\Models\OrderPayWithholdModel::updateOrCreate($order_pay_withhold_data);

                        if (!$order_pay_withhold_id->getQueueableId()) {
                            $arr[$item['withhold_id'] . 'order_pay_withhold'] = $order_pay_withhold_data;
                        }
                    }
					
            // 代扣 与 业务 关系表 order_pay_withhold_business
                    $order_pay_withhold_business_data = [
                        'withhold_no'       => $out_agreement_no,               // '业务系统代扣编码',
                        'business_type'     => 1,                           // '业务类型',
                        'business_no'       => $order_info->order_no,                    // '业务编号',
                        'bind_time'         => strtotime($item['sign_time']),// '绑定时间',
                    ];
					if( $order_info->order_status != '1' ){// 无效订单时，写入
						$order_pay_withhold_business_data['unbind_time'] = $order_info->update_time;// 解绑事件
					}

                    // 没有记录则添加
                    $order_pay_withhold_business_where = [
                        ['withhold_no', '=', $out_agreement_no],
                        ['business_type', '=', 1],
                        ['business_no', '=', $order_info->order_no]
                    ];
                    $order_pay_withhold_business_info = \App\Order\Models\OrderPayWithholdBusinessModel::query()
                        ->where($order_pay_withhold_business_where)->first();

                    if(!$order_pay_withhold_business_info){
                        $order_pay_withhold_business_id = \App\Order\Models\OrderPayWithholdBusinessModel::updateOrCreate($order_pay_withhold_business_data);
                        if(!$order_pay_withhold_business_id->getQueueableId()){
                            $arr[$item['withhold_id'].'order_pay_withhold_business'] = $order_pay_withhold_business_data;
                        }
                    }
                }

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
