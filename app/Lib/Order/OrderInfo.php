<?php
/**
 * author: heaven
 * 订单供第三方的接口
 * 2018-05-14
 */

namespace App\Lib\Order;

use App\Lib\ApiStatus;

    class OrderInfo {

        /**
         * @param array 数组    ['order_no'] = A511125156960043
         * 获取订单详情
         */
     public function getOrderInfo($param)
     {

            if (empty($param)) return apiResponse([],ApiStatus::CODE_10104);
            if (isset($param['order_no']) && !empty($param['order_no']))
            {
                $data = config('tripartite.Interior_Order_Request_data');
                $data['method'] ='api.order.orderdetail';
                $data['params'] = [
                    'user_id'=>$param['order_no'],
                ];
                $baseUrl = config("tripartite.API_INNER_URL");
                $info = Curl::post($baseUrl, json_encode($data));

                return $info;

            }
            return apiResponse([],ApiStatus::CODE_10104);

     }





    }






