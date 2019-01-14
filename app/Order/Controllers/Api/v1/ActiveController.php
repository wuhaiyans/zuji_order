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
        ini_set('max_execution_time', '0');

        try{
            $limit  = 1;
            $page   = 1;
            $sleep  = 10;
            $code   = "SMS_113461290";


            // 查询总数
            $total = OrderActive::query()
                ->where([
                    ['status', '=', 0]
                ])
                ->count();
            $totalpage = ceil($total/$limit);

            \App\Lib\Common\LogApi::debug('[sendMessage:发送短信总数为:' . $total);
//            do {
                $result = OrderActive::query()
                    ->where([
                        ['status', '=', 0]
                    ])
                    ->orderby('id','ASC')
                    ->forPage($page,$limit)
                    ->get()
                    ->toArray();
//                if(empty($result)){
//					break;
//                }
//            p($result);

                foreach($result as $item){

                    $mobile         = trim($item['mobile']);

                    $url = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.huishoubao.nqy';

                    // 短信参数
                    $dataSms =[
                        'realName' => trim($item['realname']),
                        'lianjie'  => createShortUrl($url),
                    ];
                    p($dataSms);

					// 发送短信
					\App\Lib\Common\SmsApi::sendMessage($mobile, $code, $dataSms);

                    \App\Order\Models\OrderActive::where(
                        ['id'=>$item['id']]
                    )->update(['status' => 1]);
                }
                \App\Lib\Common\LogApi::debug('[sendMessage:发送短信页数为:' . $page);
                $page++;
                sleep($sleep);
//            } while ($page <= $totalpage);

            \App\Lib\Common\LogApi::debug('[sendMessage发送短信总数为:' . $total);

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[sendMessage]活动运营短信', ['msg'=>$exc->getMessage()]);
        }
    }


}
