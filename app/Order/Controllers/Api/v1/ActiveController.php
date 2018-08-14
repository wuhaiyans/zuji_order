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
//            $code   = "SMS_113461176";
            $code   = "SMS_113461177";


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

                $webUrl = env('WEB_H5_URL');
//                $url = isset($webUrl) ? $webUrl : 'https://h5.nqyong.com/';
//                $url = $url  . 'https://h5.nqyong.com/myBillDetail?';
				$url = 'https://h5.nqyong.com/myBillDetail?';


                foreach($result as $item){

//                    $orderInfo = \App\Order\Models\OrderGoods::where(['order_no'=>$item['order_no']])->first();
//                    $orderInfo = objectToArray($orderInfo);

                    $urlData = [
                        'orderNo'       => $item['order_no'],     //  订单号
                        'zuqi_type'     => $item['zuqi_type'],    //  租期类型
                        'id'            => $item['instalment_id'],//  分期ID
                        'appid'         => $item['appid'],        //  商品编号
                        'goodsNo'       => $item['goods_no'],     //  商品编号
                    ];

                    $zhifuLianjie = $url . createLinkstringUrlencode($urlData);

                    $dataSms = [
                        'realName'      => $item['realname'],
                        'orderNo'       => $item['order_no'],
                        'goodsName'     => $item['goods_name'],
                        'zuJin'         => $item['amount'],
                        'createTime'    => '2018-08-15',
                        'zhifuLianjie'  => createShortUrl($zhifuLianjie),
                        'serviceTel'    => config('tripartite.Customer_Service_Phone'),

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
                \App\Lib\Common\LogApi::notify("提前还款短信", $arr);
            }

        }catch(\Exception $exc){
            \App\Lib\Common\LogApi::debug('[活动短信发送失败]', ['msg'=>$exc->getMessage()]);
        }
    }


}
