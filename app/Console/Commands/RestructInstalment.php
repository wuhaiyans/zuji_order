<?php

namespace App\Console\Commands;

use App\Lib\Common\LogApi;
use Illuminate\Console\Command;


class RestructInstalment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:RestructInstalment';

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
        $total = \App\Order\Models\OrderGoodsInstalment::query() ->where([
            ['withhold_day', '=', 0]
        ])->count();

        $bar = $this->output->createProgressBar($total);

        try{
            $limit  = 500;
            $page   = 1;
            $totalpage = ceil($total/$limit);
            $arr =[];
            do {
                $result = \App\Order\Models\OrderGoodsInstalment::query()
                    ->select('id','term','day')
                    ->where([
                        ['withhold_day', '=', 0]
                    ])
                    ->forPage($page,$limit)
                    ->orderBy('id', 'ASC')
                    ->get()->toArray();
                $result = objectToArray($result);

                foreach($result as &$item) {

                    // 新的还款日时间戳  1534089600 2018-08-13
                    $date = withholdDate($item['term'], $item['day']);
                    $time = strtotime($date);

                    $data = [
                        'withhold_day'  => $time
                    ];
                    $res = \App\Order\Models\OrderGoodsInstalment::query()
                        ->where(['id'=>$item['id']])
                        ->update($data);
                    if($res){
                        $arr[]  = $item['id'];
                    }

                    $bar->advance();
                }

                $page++;
                sleep(2);
            } while ($page <= $totalpage);
            if(count($arr) > 0){
                LogApi::notify("分期备注信息修改",$arr);
            }
            $bar->finish();
            echo "修改成功";die;
        }catch (\Exception $e){
            echo $e->getMessage();
            die;
        }

    }





}
