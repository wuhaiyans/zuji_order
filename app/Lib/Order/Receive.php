<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:50
 */

namespace App\Lib\Order;
use App\Lib\Curl;
/**
 * Class Delivery
 * 与收发货相关
 */
class Receive
{

    /**
     * @param $order_no
     * $business_key业务类型
     * @param $data
     *
     * 收货系统 检测结果反馈   //废弃
     *  $data = [
    [
    'refund_no' => '123',
    'check_result' => 'success',//是否合格 fasle/success
    'check_description' => '原因',
    'evaluation_time' => '123123123',//检测时间
    'price' => '342'
    ],
    [
    'refund_no' => '123',
    'check_result' => 'success',//是否合格 fasle/success
    'check_description' => '原因',
    'evaluation_time' => '123123123',//检测时间
    'price' => '21'
    ]
    ];
     */

    public static function checkResult($order_no, $business_key,$data)
    {
        try{
            $base_api = config('ordersystem.ORDER_API');

            $response = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.Return.isQualified',//模拟
                'data' => ['order'=>$order_no,'business_key'=>$business_key,'data'=>$data]
            ]);
            $res = json_decode($response);
            if ($res->code != 0) {
                return false;
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }




}