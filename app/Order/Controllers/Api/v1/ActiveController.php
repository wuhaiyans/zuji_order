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
            $limit  = 1;
            $page   = 1;
            $sleep  = 20;
            $code   = "SMS_113461070";


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

                    $webUrl = env('WEB_H5_URL');
                    $url = isset($webUrl) ? $webUrl : 'https://h5.nqyong.com/';
                    $url = $url  . 'myBillDetail?';

                    $urlData = [
                        'orderNo'       => $item['order_no'],     //  订单号
                        'zuqi_type'     => $item['zuqi_type'],         //  租期类型
                        'id'            => $item['id'],           //  分期ID
                        'appid'         => $item['appid'],             //  商品编号
                        'goodsNo'       => $item['goods_no'],     //  商品编号
                    ];

                    $zhifuLianjie = $url . createLinkstringUrlencode($urlData);

                    // 短信参数
                    $dataSms =[
                        'realName'      => $item['realname'],
                        'zuJin'         => $item['amount'],
                        'zhifuLianjie'  => createShortUrl($zhifuLianjie),
                        'serviceTel'    => config('tripartite.Customer_Service_Phone'),
                    ];
					// 发送短信
					\App\Lib\Common\SmsApi::sendMessage($item['mobile'], $code, $dataSms);

                    \App\Order\Models\OrderActive::where(
                        ['id'=>$item['id']]
                    )->update(['status' => 1]);
                die;
                }
                die;
//                sleep($sleep);
            } while (true);

            if(count($arr) > 0){
                \App\Lib\Common\LogApi::notify("[sendMessage]活动运营短信", $arr);
            }

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[sendMessage]活动运营短信', ['msg'=>$exc->getMessage()]);
        }
    }


}
