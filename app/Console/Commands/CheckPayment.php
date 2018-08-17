<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Order\Models\OrderPayModel;
use App\Lib\Payment\CommonPaymentApi;
use App\Lib\ApiException;
use Illuminate\Support\Facades\DB;

class CheckPayment extends Command
{
    /**
     * 检测支付是否成功
	 * 请求 支付系统 查询支付状态，
     *
     * @var string
     */
    protected $signature = 'command:CheckPayment {--max_id=} {--min_id=} {--p=}';

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
		$query_counter = 0;
		$query_error_counter = 0;
		$pay_status_error_counter = 0;
		
		$pagesize = 10;
		$where = [];
		
		$page = $this->option('p');
		$max_id = intval($this->option('max_id'));
		$min_id = intval($this->option('min_id'));
		if( $max_id<$min_id ){
			echo 'ID区间错误';exit;
		}
		
		// 
		if( $max_id>0 ){
			$where[] = ['id','<=',$max_id];
		}
		if( $min_id>0 ){
			$where[] = ['id','>=',$min_id];
		}
		
		$order_pay_model = new OrderPayModel();
		
		// 总数
		$total = $order_pay_model->where( $where )
				->count('id');
		
		$bar = $this->output->createProgressBar($total);
		
		$p_count = ceil($total/$pagesize);
		$min = $min_id;
		$p = 0;
		$payment_no_err_list = [];
		while($p<$p_count){
			++$p;
			$where = [
				['id','>=',$min],
				['id','<=',$max_id]
			];
			
			// 更新最新ID值
			$min += $pagesize; 
					
			// 支付单列表
			$order_pay_list = $order_pay_model->where($where)
					->select([
						'id','user_id','order_no',
						'payment_status','payment_channel','payment_amount','payment_no',
					])
					->limit($pagesize)
					->orderBy('id','ASC')
					->get();
			foreach( $order_pay_list as $item ){
				$bar->advance();
				// 跳过已经成功的支付单
				// 如果未支付是，则请求支付接口，查询支付状态
				if( $item->payment_status == 1 && $item->payment_no ){
					try{
						++$query_counter;
						// 查询 支付系统接口
						$payment_info = CommonPaymentApi::query([
							'out_payment_no' => $item->payment_no,
						]);
						// 如果已经支付成功，（因为某种原因当值业务中的支付状态未发生变化），需要触发异步通知
						if( $payment_info['status']=='success' ){
							// 记录 支付异常的记录
							++$pay_status_error_counter;
						}
					} catch (ApiException $ex) {
						// 接口查询错误
						++$query_error_counter;
						$payment_no_err_list[] = $item->payment_no;
					}
				}
			}
			
		}
		
        $bar->finish();
		
		echo "完毕({$total})\n";
		echo "Query Counter: {$query_counter}\n";
		echo "Query Error: {$query_error_counter}\n";
		echo "Status Error: {$pay_status_error_counter}\n";
		echo implode(',',$payment_no_err_list);
		exit;
		
    }


}
