<?php
/**
 *
 * Date: 2018/5/8
 * Time: 10:50
 */
namespace App\Lib\Refund;
use App\Lib\Curl;

/**
 * Class Delivery
 * 与收货相关
 */
class Receive
{

    /**
     * @param $order_no
     * $business_key业务类型
     * @param $data
     *  $data = [
     * [
     * 'sku_no' => '123',
     * 'good_id' => '123',
     * 'imei' => 'abcde',
     * 'check_result' => 'success',//是否合格 fasle/success
     * 'check_description' => '原因',
     * 'serial_number' => "sdsdsd",//金额
     * 'check_time' => '1526030858'
     * ],
     * ['sku_no' => '123',
     * 'good_id' => '123',
     * 'imei' => 'abcde',
     * 'check_result' => 'success',//是否合格 fasle/success
     * 'check_description' => '原因',
     * 'serial_number' => "sdsdsd",//金额
     * 'check_time' => '1526030858'
     * ]
     * ];
     *
     * 收货系统 检测结果反馈
     */
    public static function checkResult($order_no, $business_key,$data)
    {
        $base_api = config('ordersystem.ORDER_API');

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.Return.isQualified',//模拟
            'data' => json_encode(['order'=>$order_no,'business_key'=>$business_key,'data'=>$data])
        ]);

        return $response;


    }

}