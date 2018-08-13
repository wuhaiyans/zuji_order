<?php

namespace App\Order\Controllers\Api\v1;
use App\Order\Modules\Service;
use App\Order\Models\OrderActive;


class ActiveController extends Controller
{

    /**
     * 发送短信接口
     * @return bool
     */
    public function sendMessage(){
        try{
            $arr =[];
            $limit  = 5;
            $page   = 1;
            $sleep  = 20;
            $code   = "";



            $total = OrderActive::query()->where(['status' => 0])->count();
            $totalpage = ceil($total/$limit);

            do {
                $result = OrderActive::query()
                    ->where([
                        ['status', '=', 0]
                    ])
                    ->forPage($page,$limit)
                    ->get()
                    ->toArray();
                if(!$result){
                    continue;
                }

                foreach($result as $item){

                    $dataSms = [


                    ];





                    // 发送短信
                    $result = \App\Lib\Common\SmsApi::sendMessage($item['mobile'], $code, $dataSms);
                    if($result){
                        \App\Order\Models\OrderActive::where(
                            ['id'=>$item['id']]
                        )->update(['status' => 1]);
                    }
                }



                $page++;
                sleep($sleep);
            } while ($page <= $totalpage);

            if(count($arr) > 0){
                \App\Lib\Common\LogApi::notify("提前还款短信", $arr);
            }

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[活动短信发送失败]', ['msg'=>$exc->getMessage()]);
        }
    }


}
