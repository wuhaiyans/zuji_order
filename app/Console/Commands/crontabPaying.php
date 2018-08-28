<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Lib\Common\LogApi;

class crontabPaying extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:paying';

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

        $total = \App\Order\Models\OrderGoodsInstalment::query()
            ->where([
                ['status', '=', \App\Order\Modules\Inc\OrderInstalmentStatus::PAYING],
            ])->count();

        LogApi::info('[updateInstalmentStatus]分期支付中状态总数为：'.$total);
        // 支付中分期列表
        $list = \App\Order\Models\OrderGoodsInstalment::query()
            ->select('id','update_time')
            ->where([
            ['status', '=', \App\Order\Modules\Inc\OrderInstalmentStatus::PAYING],
        ])->get();
        $list = objectToArray($list);

        $num = 0;
        foreach($list as $item){
            // 分期 更新时间 如果大于一小时 则修改为失败
            $pastTimes = time() - 3600;

            if($pastTimes >= $item['update_time']){
                
                $instalmentStatus = \App\Order\Models\OrderGoodsInstalment::query()
                    ->where(['id'=>$item['id']])
                    ->update(['status' => \App\Order\Modules\Inc\OrderInstalmentStatus::FAIL,'update_time' => time()]);
                if(!$instalmentStatus){
                    LogApi::error('[updateInstalmentStatus]修改分期状态为失败：'.$item['id']);
                }
                ++$num;
            }
        }

        // 记录总数
        LogApi::info('[updateInstalmentStatus]修改支付中状态为失败数目为:'.$num);

    }

}
