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
            $limit  = 50;
            $page   = 1;
            $sleep  = 20;
            $code   = "SMS_113461190";


            do {
                $result = OrderActive::query()
                    ->where([
                        ['status', '=', 0]
                    ])
                    ->orderby('id','ASC')
                    ->forPage($page,$limit)
                    ->get()
                    ->toArray();
                if(empty($result)){
					break;
                }

                foreach($result as $item){

                    $dataSms = [
                        'a'      => "",
                    ];
					// 发送短信
					\App\Lib\Common\SmsApi::sendMessage($item['mobile'], $code, $dataSms);

                    \App\Order\Models\OrderActive::where(
                        ['id'=>$item['id']]
                    )->update(['status' => 1]);
                }
                sleep($sleep);
            } while (true);

            if(count($arr) > 0){
                \App\Lib\Common\LogApi::notify("[sendMessage]活动运营短信", $arr);
            }

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[sendMessage]活动运营短信', ['msg'=>$exc->getMessage()]);
        }
    }


}
