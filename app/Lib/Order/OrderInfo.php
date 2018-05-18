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
         * @param array 数组    ['order_no'] = A511125156960043
         * 获取订单详情
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
                $info = Curl::post($baseUrl, json_encode($data));

                return apiResponse($info,ApiStatus::CODE_0);

            }
            return apiResponse([],ApiStatus::CODE_10104);

     }
        /**
         * @param array 数组    array('order_no'=>'12312313','goods_no'=>"23123123")
         * 整单退款时 array('order_no'=>'12312313','goods_no'=>"")
         * 单个商品退款时array('order_no'=>'12312313','goods_no'=>"23123123")
         * 退款成功更新状态
         */
        public function updateStatus($params){
            $base_api = config('tripartitle.API_INNER_URL');

            $response = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.Return.updateStatus',//模拟
                'data' => json_encode(['params'=>$params])
            ]);

            return $response;
        }


        /**
         * 根据应用来源获取应用名称
         * @param int   $appId
         * return json
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






