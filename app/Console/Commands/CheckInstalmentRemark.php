<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class CheckInstalmentRemark extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CheckInstalmentRemark';

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

        $total = \DB::connection('mysql_01')->table('zuji_order2_instalment_remark')->count();

        $bar = $this->output->createProgressBar($total);
        try{
            $limit  = 500;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $result = \DB::connection('mysql_01')->table('zuji_order2_instalment_remark')
                    ->forPage($page,$limit)
                    ->orderBy('id', 'ASC')
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item) {

                    // 查询旧系统分期信息    term
                    $oldInstalmentInfo = \DB::connection('mysql_01')->table('zuji_order2_instalment')
                        ->where(['id'=>$item['instalment_id']])
                        ->first();
                    $oldInstalmentInfo = objectToArray($oldInstalmentInfo);
                    if(!$oldInstalmentInfo){
                        continue;
                    }

                    // 查询旧系统 订单信息  order_no
                    $orderInfo = \DB::connection('mysql_01')->table('zuji_order2')
                        ->where(['order_id'=>$oldInstalmentInfo['order_id']])
                        ->first();
                    $orderInfo = objectToArray($orderInfo);
                    if(!$orderInfo){
                        continue;
                    }

                    // 查询新系统 分期信息  id
                    $newInstalmentInfo = \App\Order\Models\OrderGoodsInstalment::query()
                        ->where([
                            ['order_no', '=', $orderInfo['order_no']],
                            ['term', '=', $oldInstalmentInfo['term']],
                        ])
                        ->first();
                    $newInstalmentInfo = objectToArray($newInstalmentInfo);
                    if(!$newInstalmentInfo){
                        continue;
                    }

                    // 更新新系统 备注信息表
                    $ret = \App\Order\Models\OrderGoodsInstalmentRemark::where(
                        ['id'=>$item['id']]
                    )->update(['instalment_id' => $newInstalmentInfo['id']]);
                    if($ret){
                        $arr[] = $item['id'];
                    }
//                    die;
                    $bar->advance();
                }

                $page++;
//                sleep(2);
            } while ($page <= $totalpage);
            if(count($arr)>0){
                LogApi::notify("分期备注信息修改",$arr);
            }
            $bar->finish();
            echo "导入成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
