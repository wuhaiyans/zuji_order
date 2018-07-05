<?php
/**
 *
 * Date: 2018/5/8
 * Time: 10:50
 */
namespace App\Lib\Refund;
use App\Lib\Curl;


class Refund
{

    /**
     * 'order_no'      =>''//订单编号
     *'business_type' => '',	// 业务类型
     *
     * 'business_no'	=> '',	// 业务编码
     *
     * 'status'		=> '',	// 支付状态  processing：处理中；success：支付完成

     */
    public function refundUpdate($params){
        try{
            $base_api = config('ordersystem.ORDER_API');
            $response = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.Return.refundUpdate',//模拟
                'data' => json_encode(['params'=>$params])
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