<?php
/**
 * author: heaven
 * 订单供第三方的接口
 * 2018-05-14
 */

namespace App\Lib\Order;
use App\Lib\Curl;
use App\Lib\ApiStatus;

class OrderInfo {

        /**
         * 获取订单详情
         * Author: heaven
         * @param $param  array 数组    ['order_no'] = A511125156960043
         * @return \Illuminate\Http\JsonResponse
         */
     public static function getOrderInfo($param)
     {

            if (empty($param)) return apiResponse([],ApiStatus::CODE_10104);
            if (isset($param['order_no']) && !empty($param['order_no']))
            {
                $data = config('tripartite.Interior_Order_Request_data');
                $data['method'] ='api.order.orderdetail';
                $data['params'] = [
                    'order_no'=>$param['order_no'],
                ];
                $baseUrl = config("tripartite.API_INNER_URL");
                $info = Curl::post($baseUrl, $data);
                return $info;
//                return apiResponse($info,ApiStatus::CODE_0);

            }
            return false;

//            return apiResponse([],ApiStatus::CODE_10104);
     }
    /**
     * 退款成功回调通知
     * 'order_no'      =>''//订单编号
     *'business_type' => '',	// 业务类型
     *
     * 'business_no'	=> '',	// 业务编码
     *
     * 'status'		=> '',	// 支付状态  processing：处理中；success：支付完成

     */
     public function updateStatus($params){
         try{
            $base_api = config('tripartitle.API_INNER_URL');
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



        /**
         * 根据应用来源获取应用名称
         * Author: heaven
         * @param $appId
         * @return bool|\Illuminate\Http\JsonResponse|string
         */
        public static function getAppidInfo($appId)
        {

            if (empty($appId)) return apiResponse([],ApiStatus::CODE_10104);
                $data = config('tripartite.Interior_Goods_Request_data');
                $data['method'] ='zuji.channel.appid.get';
                $data['params'] = [
                    'appid'=>$appId,
                ];
                $baseUrl = config("tripartite.Interior_Goods_Url");
                $info = Curl::post($baseUrl, json_encode($data));
                if (!empty($info)) {

                    $appInfo = json_decode($info, true);

                    return isset($appInfo['data']['appid']['name'])?$appInfo['data']['appid']['name']:'';

                }
                return false;

        }



    }






