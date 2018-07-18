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
  public function handle(){

    $total = \DB::connection('mysql_01')->table('zuji_order2_instalment')->count();

    $bar = $this->output->createProgressBar($total);
    try{
      $limit  = 300;
      $page   = 1;
      $totalpage = ceil($total/$limit);
      $arr =[];

      do {
          $result = \DB::connection('mysql_01')->table('zuji_order2_instalment')
              ->forPage($page,$limit)
              ->orderBy('id', 'DESC')
              ->get()->toArray();
          $result = objectToArray($result);

          foreach($result as &$item) {
            // 查询订单信息

            $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')->select('order_no', 'user_id', 'zujin', "appid", "business_key")->where(['order_id' => $item['order_id']])->first();
            $orderInfo = objectToArray($orderInfo);
            if(!$orderInfo){
              continue;
            }

            // 去除小程序分期
            $isAllow = \App\Console\Commands\ImportOrder::isAllowImport($orderInfo['order_no']);
            if(!$isAllow){
              continue;
            }


            $data['id']               = $item['id'];
            $data['order_no']         = $orderInfo['order_no'];
            $data['goods_no']         = createNo();
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
            $ret = \App\Order\Models\OrderGoodsInstalment::updateOrCreate($data);
            if(!$ret->getQueueableId()){
              $arr[$item['id']] = $item;
            }
          }

          $bar->advance();

          $page++;
          sleep(2);
        } while ($page <= $totalpage);
          if(count($arr)>0){
            LogApi::notify("订单用户回访数据导入失败",$arr);
          }
        $bar->finish();
        echo "导入成功";die;
      }catch (\Exception $e){
        echo $e->getMessage();
        die;
      }

  }





}
