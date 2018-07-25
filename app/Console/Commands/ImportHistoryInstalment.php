<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class ImportHistoryInstalment extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'command:Instalment {--min_id=} {--max_id=}';

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
  public function handle(){
	 
	$_count1 = 0;
	$_count2 = 0;

	$min_id = intval($this->option('min_id'));
	$max_id = ($this->option('max_id'));
    $total = \DB::connection('mysql_01')->table('zuji_order2_instalment')
			->where([
				['id','>=',$min_id],
				['id','<=',$max_id]
			])->count('id');
	
    $bar = $this->output->createProgressBar($total);
    try{
      $limit  = 1000;
      $page   = 1;
      $totalpage = ceil($total/$limit);
      $arr =[];
	  
	  // 开始位置
	  $last_id = $min_id-1;
	  
      while($last_id<$max_id) {
		  // 重新调整位置
		  $last_id +=1;
          $result = \DB::connection('mysql_01')->table('zuji_order2_instalment')
              ->where([
				['id','>=',$last_id],
				['id','<=',$max_id],
			  ])
              ->limit($limit)
              ->orderBy('id', 'ASC')
              ->get()->toArray();
          $result = objectToArray($result);
		  
		  if( count($result) == 0 ){
			  break;
		  }
		  
          foreach($result as &$item) {
			++$_count1;
			$bar->advance();
			$last_id = $item['id'];
			
            // 查询订单信息

            $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_no', 'goods_id', 'user_id', 'zujin', "appid", "business_key")->where(['order_id' => $item['order_id']])->first();
            $orderInfo = objectToArray($orderInfo);

            if(!$orderInfo){
              continue;
            }

            // 去除小程序分期
            $isAllow = \App\Console\Commands\ImportOrder::isAllowImport($orderInfo['order_no']);
            if(!$isAllow){
				++$_count2;
				continue;
            }

            $data['id']               = $item['id'];
            $data['order_no']         = $orderInfo['order_no'];
            $data['goods_no']         = $orderInfo['goods_id'];
            $data['user_id']          = $orderInfo['user_id'];

            $data['term']             = $item['term'];
            $data['times']            = $item['times'];
            $data['discount_amount']  = $item['discount_amount'];
            $data['status']           = $item['status'];
            $data['payment_time']     = $item['payment_time'];
            $data['update_time']      = $item['update_time'];
            $data['remark']           = $item['remark'];
            $data['fail_num']         = $item['fail_num'];
            $data['unfreeze_status']  = $item['unfreeze_status'];


            $data['day']              = 15;
            $data['original_amount']  = $orderInfo['zujin'] / 100;
            $data['amount']           = $item['amount'] / 100;

            //有记录则跳出
            $info = \App\Order\Models\OrderGoodsInstalment::query()
                ->where([
                    ['id', '=', $item['id']]
                ])->first();
            if($info){
              continue;
            }

            // 插入数据
            $ret = \App\Order\Models\OrderGoodsInstalment::insert($data);
            if(!$ret){
              $arr[$item['id']] = $item;
            }
          }


        } 
          if(count($arr)>0){
            LogApi::notify("订单分期数据导入失败",$arr);
          }
        $bar->finish();
		echo "导入成功（{$_count1},{$_count2}）";die;
      }catch (\Exception $e){
        echo $e->getMessage();
        die;
      }

  }





}
