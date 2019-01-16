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
            $limit  = 50;
            $page   = 1;
            $code   = "SMS_113461292";

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
                        'realName'      => $item['realname'],
                    ];

                    // 发送短信
                     \App\Lib\Common\SmsApi::sendMessage($item['mobile'], $code, $dataSms);

                    \App\Order\Models\OrderActive::where(
                        ['id'=>$item['id']]
                    )->update(['status' => 1]);
                }
                // sleep($sleep);

            } while (true);

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[activeSsendMessage:活动短信发送失败]', ['msg'=>$exc->getMessage()]);
        }
    }


}
