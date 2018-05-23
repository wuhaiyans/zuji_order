<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\Curl;

/**
 * Class Delivery
 * 收货模块
 */
class Receive
{
    /**
     * 创建待收货
     * type 类型:退 换 还 ...
     *
     * $data = [
     *  logistics_id, 【必须】
     *  logistics_no, 【必须】
     *  receive_detail = [
     *      [
     *          serial_no 【必须】
     *          quantity 【必须】
     *          imei  【可以没有】
     *      ]
     *  ]
     * ]
     *
     *
     */
    public static function create($order_no, $type, $data)
    {
        $receive_detail = [];

        $logistics_id = isset($data['logistics_id']) ? $data['logistics_id'] : 0;
        $logistics_no = isset($data['logistics_no']) ? $data['logistics_no'] : 0;

        $detail = $data['receive_detail'];

        if (is_array($detail)) {
            foreach ($detail as $d) {
                if (!$d['serial_no'] || !$d['quantity']) continue;
                
                $receive_detail[] = [
                    'serial_no' => $d['serial_no'],
                    'quantity'  => $d['quantity'],
                    'imei'      => isset($d['imei']) ? $d['imei'] : ''
                ];
            }
        }

        $result = [
            'order_no' => $order_no,
            'receive_detail' => $receive_detail,
            'logistics_id' => $logistics_id,
            'logistics_no' => $logistics_no,
            'type' => $type
        ];

        $base_api = config('tripartite.warehouse_api_uri');

        return Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.receive.create',//模拟
            'params' => json_encode($result)
        ]);

    }


}