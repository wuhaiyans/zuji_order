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
     * 'id'      =>''//退换货id
     *'business_type' => '',	// 业务类型
     *
     * 'business_no'	=> '',	// 业务编码
     *
     * 'status'		=> '',	// 支付状态  processing：处理中；success：支付完成

     */
    public function refundUpdate($params){
        $base_api = config('tripartitle.API_INNER_URL');

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.Return.refundUpdate',//模拟
            'data' => json_encode(['params'=>$params])
        ]);

        return $response;
    }

}