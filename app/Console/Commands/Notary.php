<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order\Models\OrderPayModel;
use App\Lib\Payment\CommonPaymentApi;
use App\Lib\ApiException;

class Notary extends Command
{
    /**
     * 检测支付是否成功
	 * 请求 支付系统 查询支付状态，
     *
     * @var string
     */
    protected $signature = 'command:Notary {--job=} {--create_time=}';

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
		try {

			// A927147474668405	乐百分
			// A903163794685271 支付宝代扣+预授权
			//
//			$s = $this->_notary( 'AB07175752759085', 'fenqi_overdue_90days' );
//			var_dump( $s );exit;
		$job = $this->option('job');
		
		if( $job == 'fenqi_overdue' ){
			$this->_fenqi_overdue();
		}
		
		elseif( $job == 'giveback_overdue' ){
			$this->_giveback_overdue();
		}
		
		elseif( $job == 'check' ){
			$this->_check();
		}
		elseif( $job == 'upload' ){
			$this->_upload();
		}
		
		else{
			echo "--job=\t\tfenqi_overdue / giveback_overdue / check\n";
		}
			
		} catch (\Exception $exc) {
			echo $exc->getMessage();
		}
		
	}
	
	
    /**
	 * 到期未还
     * @return mixed
     */
    private function _giveback_overdue()
    {
		\App\Lib\Common\LogApi::setSource('command_Notary_giveback');
		
		$ok_count = 0;
		$not_cert_count = 0;
		$not_order_count = 0;
		
		echo '逾期30天未归还设备的订单数：';
		// 逾期30天未归还设备的订单数
		$timestamp = time() - 30*86400;
		$count = \App\Order\Models\OrderGoods::where([['end_time', '<' , $timestamp],['goods_status', '=' , 10],])
				->distinct('order_no')
				->count('order_no');
		echo $count."\n";
        $bar = $this->output->createProgressBar($count);
		
		
		$page = 1;
		$pagesize = 10;
		$pagecount = ceil($count/$pagesize);
		
		while ( $page <= $pagecount ){
			// 订单编号列表	
			$order_no_list = \App\Order\Models\OrderGoods::where([['end_time', '<' , $timestamp],['goods_status', '=' , 10],])
					->select('order_no')
					->distinct('order_no')
					->orderBy('order_no','ASC')
					->forPage($page,$pagesize)
					->get();
			foreach( $order_no_list->toArray() as $it ){
				$bar->advance();
				$s = $this->_notary( $it['order_no'], 'giveback_overdue_30days' );
				if( $s == 0 ){
					++$ok_count;
				}
				elseif( $s == 1 ){
					++$not_order_count;
				}
				elseif( $s == 2 ){
					++$not_cert_count;
				}
			}
			
			++$page;
		}
		
		$bar->finish();
		echo "结束\n";
		echo "总数：{$count}\n";
		echo "成功：{$ok_count}\n";
		echo "未实名：{$not_cert_count}\n";
		echo "订单错误：{$not_order_count}\n";
    }
	
    /**
	 * 账单预期
     * @return mixed
     */
    private function _fenqi_overdue()
    {
		\App\Lib\Common\LogApi::setSource('command_Notary_fenqi');
		
		$ok_count = 0;
		$not_cert_count = 0;
		$not_order_count = 0;
		
		echo '逾期90天未还款订单数：';
		// 顺序读取逾期90天未还款的订单
		$timestamp = time() - 90*86400;
		$count = \App\Order\Models\OrderGoodsInstalment::where([['withhold_day', '<' , $timestamp],])
				->whereIn('status',[1,3,5])
				->distinct('order_no')
				->count('order_no');
		echo $count."\n";
        $bar = $this->output->createProgressBar($count);
		
		
		$page = 1;
		$pagesize = 10;
		$pagecount = ceil($count/$pagesize);
		
		while ( $page <= $pagecount ){
			// 订单编号列表	
			$order_no_list = \App\Order\Models\OrderGoodsInstalment::where([['withhold_day', '<' , $timestamp],])
					->whereIn('status',[1,3,5])
					->select('order_no')
					->distinct('order_no')
					->orderBy('order_no','ASC')
					->forPage($page,$pagesize)
					->get();
			foreach( $order_no_list->toArray() as $it ){
				$bar->advance();

				$s = $this->_notary( $it['order_no'], 'fenqi_overdue_90days' );
				if( $s == 0 ){
					++$ok_count;
				}
				elseif( $s == 1 ){
					++$not_order_count;
				}
				elseif( $s == 2 ){
					++$not_cert_count;
				}
			}
			
			++$page;
		}
		
		$bar->finish();
		echo "结束\n";
		echo "总数：{$count}\n";
		echo "成功：{$ok_count}\n";
		echo "未实名：{$not_cert_count}\n";
		echo "订单错误：{$not_order_count}\n";
    }

	
	/**
	 * 创建
	 * @param type $order_no
	 * @return int  0：成功；1：订单错误；2：未实名；3：用户错误；4：支付问题；100：存证失败
	 */
	private function _notary( string $order_no, string $phase ): int{
		
		// 订单编号
		$data = [];

		// 订单详情
		$result = \App\Order\Modules\Service\OrderOperate::getOrderInfo( $order_no );
		if( $result['code'] != 0 ){
			return 1;
		}
		$result = $result['data'];

		// 订单基本信息
		$order_info = $result['order_info'];
		
		// 没有实名数据时无法存证
		if( !$order_info['realname'] || !$order_info['cret_no'] ){
			return 2;
		}
		$data['order_info'] = [
			'order_no'	=> $order_info['order_no'],
			'mobile'	=> $order_info['mobile'],
			'realname'	=> $order_info['realname'],
			'cret_no'	=> $order_info['cret_no'],
			'user_id'	=> $order_info['user_id'],
			'pay_type_name'		=> $order_info['pay_type_name'],
			'order_amount'		=> $order_info['order_amount'], // 实际订单总金额
			'order_yajin'		=> $order_info['order_yajin'],	// 实际订单押金
			'order_insurance'	=> $order_info['order_insurance'],// 订单碎屏险
			'discount_amount'	=> $order_info['discount_amount'],// 订单优惠金额
			'goods_yajin'	=> $order_info['goods_yajin'],	// 商品押金
			'create_time'	=> date('Y-m-d H:i:s',$order_info['create_time']),
			'pay_time'		=> $order_info['pay_time']>0?date('Y-m-d H:i:s',$order_info['pay_time']):'',
		];
		
		// 查询用户和注册信息
		$user_info = \DB::connection('mysql_01')->table('zuji_member')
			->where(['id'=>$order_info['user_id']])
			->select(['id','username','mobile','register_time','register_ip'])
			->first();
		if( !$user_info ){
			return 3;
		}
		$data['user_info'] = [
			'id'			=> $user_info->id,
			'username'		=> $user_info->username,
			'mobile'		=> $user_info->mobile,
			'register_time' => $user_info->register_time>0?date('Y-m-d H:i:s',$user_info->register_time):'',
			'register_ip'	=> $user_info->register_ip,
		];
		
		// 支付信息
		$pay_info = $this->_getPayInfo( $order_no, $order_info['order_type'] );
			if( !$pay_info ){
				return 4;
			}
			$data['pay_info'] = $pay_info;
		// 兼容 历史数据没有支付时间
		if( empty($data['order_info']['pay_time']) ){// （历史数据导入时，没有支付时间的问题）
			// 先取 直接支付的时间
			if( isset($pay_info['payment_info']['third_payment_time']) ){
				$data['order_info']['pay_time'] = $pay_info['payment_info']['third_payment_time'];
			}
			// 再取 预授权的时间
			elseif( isset($pay_info['fundauth_info']['third_fundauth_time']) ){
				$data['order_info']['pay_time'] = $pay_info['fundauth_info']['third_fundauth_time'];
			}
			// 最后取 代扣签约的时间
			elseif( isset($pay_info['withhold_info']['third_withhold_time']) ){
				$data['order_info']['pay_time'] = $pay_info['withhold_info']['third_withhold_time'];
			}
		}
		
		//商品列表
		foreach( $result['goods_info'] as $it ){
			$_data = [
				'goods_no'		=> $it['goods_no'],
				'goods_name'	=> $it['goods_no'],
				'zuji_goods_sn' => $it['zuji_goods_sn'],
				'specs'			=> $it['specs'],	//规格描述
				'imei'			=> $it['imei'],		// 商品IMEI
				'zuqi_type'		=> $it['zuqi_type']==1?'短租':'长租',
				'quantity'		=> $it['quantity'],	// 数量
				'goods_yajin'	=> $it['goods_yajin'],// 商品押金
				'yajin'			=> $it['yajin'],	// 实付押金
				'zuqi'			=> $it['zuqi'].($it['zuqi_type']==1?'天':'月'),	// 租期
				'zujin'			=> $it['zujin'],	// 单位租金
				'discount_amount'=> $it['discount_amount'],	// 优惠金额（商品券）
				'coupon_amount'=> $it['coupon_amount'],	// 优惠券金额（现金券，首月0租金）
				'amount_after_discount'=> $it['amount_after_discount'],	// 优惠后的总租金（总租金（zujin*zuqi） - 优惠金额-优惠券金额（现金券金额））
				'price'			=> $it['price'],	// 实际支付金额
				'insurance'		=> $it['insurance'],	// 碎屏险
				'begin_time'	=> $it['begin_time']>0?date('Y-m-d H:i:s',$it['begin_time']):'',// 起租时间
				'end_time'		=> $it['end_time']>0?date('Y-m-d H:i:s',$it['end_time']):'',	// 结束时间
			];

			// 分期列表
			if( isset($result['instalment_info'][$it['goods_no']]) ){
				foreach( $result['instalment_info'][$it['goods_no']] as $it ){
					$_data['instalment_list'][] = [
						'business_no' => $it['business_no'],
						'term'	=> $it['term'],
						'day'	=> $it['day'],
						'times' => $it['times'],
						'amount' => $it['amount'],	// 应付金额
						'original_amount'	=> $it['original_amount'],// 原始金额
						'discount_amount'	=> $it['discount_amount'],// 优惠金额
						'payment_amount'	=> $it['payment_amount'],// 实付金额
						'status'		=> $it['status'],
						'payment_time'	=> $it['payment_time'],
					];
				}
			}
			$data['goods_list'][] = $_data;
		}

		// 收货人信息
		$data['consignee_info'] = [
			'consignee_mobile' => $order_info['consignee_mobile'],
			'consignee_name' => $order_info['name'],
			'address' => $order_info['address_info'],
		];
		
		// 发货信息
		$delivery_info = $result['goods_extend_info'];
		$data['delevary'] = [
			'logistics_name'	=> $delivery_info['logistics_name'],	// 物流渠道
			'logistics_no'		=> $delivery_info['logistics_no'],		// 物流单号
			'delivery_time'		=> $order_info['delivery_time']>0?date('Y-m-d H:i:s',$order_info['delivery_time']):'',// 物流发货时间
			'receive_time'		=> $order_info['receive_time']>0?date('Y-m-d H:i:s',$order_info['receive_time']):'', // 物流签收时间
		];

		// 电子合同
		$contract_info = \DB::connection('mysql_01')->table('zuji_order2_contract')
			->where(['order_no'=>$order_info['order_no']])
			->select(['download_url','viewpdf_url'])
			->first();
		if( $contract_info ){
			$contract_content = '';
			$contract_file = __DIR__.'/_contract/'.$order_no.'.pdf';
			if(file_exists($contract_file) ){
				$contract_content = file_get_contents( $contract_file );
			}else{
				$contract_content = file_get_contents( $contract_info->download_url );
				if( $contract_content ){
					file_put_contents($contract_file, $contract_content);
				}
			}
			// 合同内容哈希
			$hash = hash('sha256', $contract_content);
			$data['contract_info'] = [
				'download_url' => $contract_info->download_url,
				'viewpdf_url' => $contract_info->viewpdf_url,
				'hash' => $hash,
			];
		}
		
		
		$notary_content = json_encode($data);
		file_put_contents(__DIR__.'/abc.txt', $notary_content."\n", FILE_APPEND);
		
		//-+--------------------------------------------------------------------
		// | 开始存证
		//-+--------------------------------------------------------------------
		
		$accountId = 'DCODMVCN';

		$notaryApp = new \App\Lib\Alipay\Notary\NotaryApp($accountId);
		
		// 开启存证事务
		if( !$notaryApp->startTransactionByBusiness($order_no, '') ){
			// 用户实名身份信息
			$customer = new \App\Lib\Alipay\Notary\CustomerIdentity();
			$customer->setCertNo($data['order_info']['cret_no']);
			$customer->setCertName($data['order_info']['realname']);
			$customer->setMobileNo($data['order_info']['mobile']);
			// 注册事务
			if( !$notaryApp->registerTransaction($order_no, '', $customer) ){
				return 100;
			}
		}
		
		
		try{
			
			// 创建 文本存证
			$notary = $notaryApp->createTextNotary( $notary_content, $phase );
//			var_dump( $notary );
//			// 上传 文本存证
//			$b = $notaryApp->uploadNotary( $notary );
//			var_dump( '文本存证：'.$notary->getTxHash(), $b );
			
			if( $data['contract_info']['hash'] ){
				// 创建 电子合同文本存证
				$notary = $notaryApp->createTextNotary( $data['contract_info']['hash'], 'electronic-contract' );
//				var_dump( $notary );
//				// 上传 文本存证
//				$b = $notaryApp->uploadNotary( $notary );
//				var_dump( '电子合同文本存证：'.$notary->getTxHash(), $b );
			}
			
		} catch (\Exception $ex) {
			\App\Lib\Common\LogApi::error('可选存证异常',$ex);
			return 100;
		}
		
		return 0;
	}
	
	/**
	 * 上传
	 * @return int
	 */
	private function _upload( ): int{
		
		$create_time = $this->option('create_time');
		$create_time = strtotime($create_time);
		if( $create_time<1 ){
			echo 'create_time is error';exit;
		}
		
		$end_time = time();
		
		// 查询
		$model = new \App\Lib\Alipay\Notary\DataModel\NotaryPhase();
		$count = $model->where([
				['create_time', '>=', $create_time],
				['create_time', '<=', $end_time],
				'upload_time' => '0',
			])->count('id');
		
		if( $count == 0 ){
			return 0;
		}
		
        $bar = $this->output->createProgressBar($count);
		
		//-+--------------------------------------------------------------------
		// | 开始存证
		//-+--------------------------------------------------------------------
		
		$accountId = 'DCODMVCN';

		$notaryApp = new \App\Lib\Alipay\Notary\NotaryApp($accountId);
		
		
		$page_size = 10;
		
		$page_count = ceil($count/$page_size);
		
		$page = 1;
		
		while( $page <= $page_count ){
			$_list = $model->where([
					['create_time', '>=', $create_time],
					['create_time', '<=', $end_time],
					'upload_time' => '0',
				])->orderBy('create_time','ASC')
				->select(['id','transaction_token'])
				->forPage($page,$page_size)
				->get();
			//
			foreach( $_list as $it ){
				$bar->advance();

				if( !$it->transaction_token ){
					continue;
				}

				//-+--------------------------------------------------------------------
				// | 开始存证
				//-+--------------------------------------------------------------------
				// 开启存证事务
				if( !$notaryApp->startTransactionByToken($it->transaction_token) ){
					continue;
				}
				// 查询存证
				if( !$notaryApp->queryNotary( $it->id, $notary ) ){
					continue;
				}
				
				// 上传存证
				$notaryApp->uploadNotary( $notary );
				var_dump( '存证txHash：'.$notary->getTxHash());
				//exit;
			}
			++$page;
		}
		$bar->finish();
		
		return 0;

	}
	
	/**
	 * 核验
	 * @return int
	 */
	private function _check( ): int{
		
		$create_time = $this->option('create_time');
		$create_time = strtotime($create_time);
		if( $create_time<1 ){
			echo 'create_time is error';exit;
		}
		
		$end_time = time();
		
		// 查询
		$model = new \App\Lib\Alipay\Notary\DataModel\NotaryPhase();
		$count = $model->where([
				['create_time', '>=', $create_time],
				['create_time', '<=', $end_time],
				['upload_time','>',0]
			])->count('id');
		if( $count == 0 ){
			return 0;
		}
		
        $bar = $this->output->createProgressBar($count);
		
		//-+--------------------------------------------------------------------
		// | 开始存证
		//-+--------------------------------------------------------------------
		
		$accountId = 'DCODMVCN';

		$notaryApp = new \App\Lib\Alipay\Notary\NotaryApp($accountId);
		
		
		$page_size = 10;
		
		$page_count = ceil($count/$page_size);
		
		$page = 1;
		
		while( $page <= $page_count ){
			$_list = $model->where([
					['create_time', '>=', $create_time],
					['create_time', '<=', $end_time],
					['upload_time','>',0]
				])->orderBy('create_time','ASC')
				->select(['id','transaction_token'])
				->forPage($page,$page_size)
				->get();
			//
			foreach( $_list as $it ){
				$bar->advance();

				if( !$it->transaction_token ){
					continue;
				}

				//-+--------------------------------------------------------------------
				// | 存证核验
				//-+--------------------------------------------------------------------
				// 开启存证事务
				if( !$notaryApp->startTransactionByToken($it->transaction_token) ){
					continue;
				}
				// 查询存证
				if( !$notaryApp->queryNotary( $it->id, $notary ) ){
					continue;
				}
				
				try{
					// 核验
					$str = $notaryApp->notaryStatus( $notary->getTxHash(), $notary->getContentHash() );
					var_dump( '存证核验：'.$str);
					
//					$str = $notaryApp->textNotaryGet( $notary->getTxHash() );
//					var_dump( '存证：'.$str);
					
				} catch (\App\Lib\Alipay\Notary\NotaryException $ex) {
					\App\Lib\Common\LogApi::error('存在核验异常：'.$ex->getErrorMessage(), $ex);
				}
			}
			++$page;
		}
		$bar->finish();
		
		return 0;

	}
	
	
	/**
	 * 支付信息
	 * @param string $order_no
	 * @return	
	 */
	private function _getPayInfo( $order_no, $order_type ){
		$data = [
			'payment_info' => null,
			'fundauth_info' => null,
			'withhold_info' => null,
		];
		
		// 订单类型：线上订单
		if( $order_type == \App\Order\Modules\Inc\OrderStatus::orderOnlineService ){
		try{
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI, $order_no);
			if( !$pay->isSuccess() ){
				return false;
			}
		
		try{
			// 支付
			if($pay->getPaymentStatus() == \App\Order\Modules\Repository\Pay\PaymentStatus::PAYMENT_SUCCESS){
				$_payment_info = \App\Order\Modules\Repository\Pay\PayQuery::getPaymentInfoByPaymentNo($pay->getPaymentNo());
				// 支付宝
				if( $pay->getPaymentChannel() == 2 ){
					// 查询支付系统的数据
					$alipay_payment = \DB::connection('pay')->table('zuji_pay_alipay_payment')
						->where(['payment_no'=>$_payment_info['out_payment_no']])
						->select(['payment_no','alipay_trade_no','gmt_payment'])
						->first();
					if( !$alipay_payment ){
						return false;
					}
					$data['payment_info'] = [
						'channel_name'	=> '支付宝支付',
						'business_payment_no'	=> $_payment_info['payment_no'],
						'paysystem_payment_no'	=> $alipay_payment->payment_no,
						'third_payment_no'		=> $alipay_payment->alipay_trade_no,
						'third_payment_time'	=> $alipay_payment->gmt_payment,
					];
				}
				// 微信
				elseif( $pay->getPaymentChannel() == 4 ){
					// 查询支付系统的数据
					$wxpay_payment = \DB::connection('pay')->table('zuji_pay_wxpay_payment')
						->where(['payment_no'=>$_payment_info['out_payment_no']])
						->select(['payment_no','transaction_id','payment_time'])
						->first();
					if( !$wxpay_payment ){
						return false;
					}
					$data['payment_info'] = [
						'channel_name'	=> '微信支付',
						'business_payment_no'	=> $_payment_info['payment_no'],
						'paysystem_payment_no'	=> $wxpay_payment->payment_no,
						'third_payment_no'		=> $wxpay_payment->transaction_id,
						'third_payment_time'	=> $wxpay_payment->payment_time>0?date('Y-m-d H:i:s',$wxpay_payment->payment_time):'',
					];
				}
				// 乐百分
				elseif( $pay->getPaymentChannel() == 5 ){
					// 查询支付系统的数据
					$lebaifen_payment = \DB::connection('pay')->table('zuji_pay_lebaifen_payment')
						->where(['payment_no'=>$_payment_info['out_payment_no']])
						->select(['payment_no','contracts_code','resp_time'])
						->first();
					if( !$lebaifen_payment ){
						return false;
					}
					$data['payment_info'] = [
						'channel_name'	=> '乐百分支付',
						'business_payment_no'	=> $_payment_info['payment_no'],
						'paysystem_payment_no'	=> $lebaifen_payment->payment_no,
						'third_payment_no'		=> $lebaifen_payment->contracts_code,
						'third_payment_time'	=> $lebaifen_payment->resp_time,
					];
				}
			}
		} catch (\App\Lib\NotFoundException $ex) {
			// 不存在支付信息
		}
			
		try{
			// 预授权信息
			if($pay->getFundauthStatus() == \App\Order\Modules\Repository\Pay\FundauthStatus::SUCCESS){
				$_fundauth_info = \App\Order\Modules\Repository\Pay\PayQuery::getAuthInfoByAuthNo($pay->getFundauthNo());
				// 支付宝
				if( $pay->getFundauthChannel() == 2 ){
					// 查询支付系统的数据
					$alipay_fundauth = \DB::connection('pay')->table('zuji_pay_alipay_fundauth')
						->where(['fundauth_no'=>$_fundauth_info['out_fundauth_no']])
						->select(['fundauth_no','alipay_fundauth_no','gmt_trans'])
						->first();
					if( !$alipay_fundauth ){
						return false;
					}
					$data['fundauth_info'] = [
						'channel_name'	=> '支付宝资金预授权',
						'business_fundauth_no'	=> $_fundauth_info['fundauth_no'],
						'paysystem_fundauth_no'	=> $alipay_fundauth->fundauth_no,
						'third_fundauth_no'		=> $alipay_fundauth->alipay_fundauth_no,
						'third_fundauth_time'	=> $alipay_fundauth->gmt_trans,
					];
				}
			}
		} catch (\App\Lib\NotFoundException $ex) {
			// 不存在预授权信息
		}
		} catch (\App\Lib\NotFoundException $ex) {
			// 订单无需支付的;
		}
		
			
			// 代扣协议，可以不存在
			try{
				// 订单关联的代扣协议
			$_business_withhold_info = \App\Order\Modules\Repository\Pay\PayQuery::getWithholdByBusiness(\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI, $order_no);
				// 代扣协议
				$_withhold_info = \App\Order\Modules\Repository\Pay\PayQuery::getWithholdInfoByWithholdNo($_business_withhold_info['withhold_no']);
				// 查询支付系统的数据
				$alipay_withhold = \DB::connection('pay')->table('zuji_pay_alipay_withhold')
					->where(['agreement_no'=>$_withhold_info['out_withhold_no']])
					->select(['agreement_no','alipay_agreement_no','sign_time'])
					->first();
				if( !$alipay_withhold ){
					return false;
				}
				$data['withhold_info'] = [
					'channel_name'	=> '支付宝代扣协议',
					'business_withhold_no'	=> $_withhold_info['withhold_no'],
					'paysystem_withhold_no'	=> $alipay_withhold->agreement_no,
					'third_withhold_no'		=> $alipay_withhold->alipay_agreement_no,
				'third_withhold_time'	=> $_business_withhold_info['bind_time']>0?date('Y-m-d H:i:s',$_business_withhold_info['bind_time']):'',// 绑定时间
				];
			} catch (\App\Lib\NotFoundException $ex) {
			// 不存在代扣签约
			}
		}
		// 订单类型：线上订单
		elseif( $order_type == \App\Order\Modules\Inc\OrderStatus::orderMiniService ){
			$mini_info = \App\Order\Models\OrderMini::where(['order_no'=>$order_no])->first();
			if( !$mini_info ){
				return $data;
			}
			$data['fundauth_info'] = [
				'channel_name'	=> '芝麻小程序资金预授权',
				'business_fundauth_no'	=> $mini_info->order_no,
				'paysystem_fundauth_no'	=> '',
				'third_fundauth_no'		=> $mini_info->zm_order_no,
				'third_fundauth_time'	=> '',
			];
			$data['withhold_info'] = [
				'channel_name'	=> '芝麻小程序代扣协议',
				'business_fundauth_no'	=> $mini_info->order_no,
				'paysystem_fundauth_no'	=> '',
				'third_fundauth_no'		=> $mini_info->zm_order_no,
				'third_fundauth_time'	=> '',
			];
		}


		
			
		return $data;

	}
	

}
