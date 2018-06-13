<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportHistoryInstalment extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'command:Instalment';

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

      $result = \DB::connection('mysql_01')->table('zuji_order2_instalment')->select('*')->orderBy('id', 'DESC')->offset(0)->limit(2)->get()->toArray();
      $result = objectToArray($result);

      foreach($result as &$item){
        // 查询订单信息
        $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_no','user_id','zujin')->where(['order_id'=>$item['order_id']])->first();
        $orderInfo = objectToArray($orderInfo);


        $item['order_no']          = $orderInfo['order_no'];
        $item['goods_no']          = createNo();
        $item['user_id']           = $orderInfo['user_id'];
        $item['day']               = 15;
        $item['original_amount']   = $orderInfo['zujin']/100;
        $item['amount']            = $item['amount']/100;
      // 插入数据
      \App\Order\Models\OrderGoodsInstalment::create($item);

    }
  }
}
