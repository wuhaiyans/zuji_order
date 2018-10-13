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
            $code   = "SMS_113461197";


            // 查询总数
            $total = OrderActive::query()
                ->where([
                    ['status', '=', 0]
                ])
                ->count();
            $total = 1;
            $totalpage = ceil($total/$limit);

            \App\Lib\Common\LogApi::debug('[sendMessage:发送短信总数为:' . $total);
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

                    $mobile         = trim($item['mobile']);

                    $url = 'https://h5.nqyong.com/myBillDetail?';

                    $urlData = [
                        'orderNo'       => trim($item['order_no']),     //  订单号
                        'zuqi_type'     => trim($item['zuqi_type']),    //  租期类型
                        'id'            => trim($item['instalment_id']),//  分期ID
                        'appid'         => trim($item['appid']),        //  商品编号
                        'goodsNo'       => trim($item['goods_no']),     //  商品编号
                    ];

                    $zhifuLianjie = $url . createLinkstringUrlencode($urlData);


                    // 短信参数
                    $dataSms =[
                        'realName'      => trim($item['realname']),
                        'goodsName'     => trim($item['goods_name']),
                        'zuJin'         => trim($item['amount']),
                        'zhifuLianjie'  => createShortUrl($zhifuLianjie),
                        'serviceTel'    => config('tripartite.Customer_Service_Phone'),
                    ];


					// 发送短信
					\App\Lib\Common\SmsApi::sendMessage($mobile, $code, $dataSms);

                    \App\Order\Models\OrderActive::where(
                        ['id'=>$item['id']]
                    )->update(['status' => 1]);
                }
                \App\Lib\Common\LogApi::debug('[sendMessage:发送短信页数为:' . $page);
                $page++;
                sleep($sleep);
            } while ($page <= $totalpage);

            \App\Lib\Common\LogApi::debug('[sendMessage发送短信总数为:' . $total);

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[sendMessage]活动运营短信', ['msg'=>$exc->getMessage()]);
        }
    }


}
