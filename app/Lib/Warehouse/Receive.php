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
     *   order_no
     *   data=[
     *      [
     *          serial_no 【可选】
     *          goods_no 【必须】
     *          quantity 【必须】
     *          imei  【可以没有】
     *      ]
     *  ]
     *
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
                    'serial_no' => $d['serial_no'],//可以不传
                    'goods_no'  => $d['goods_no'],
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
    /**
     *[
     * order_no
     * goods_info=[
     * [
     * 'goods_no' => 12,
     * 'imei1' => 0
     *
     * ],
     * ['goods_no' => 12,
     * 'imei1' => 0
     *
     * ]
     *
     *
     * ]
     * logistics_id
     * logistics_no

     * ]
     */
    public function updateLogistics($params){
        return true;
    }
    /**
     * @param $order_no
     * $business_key业务类型
     * @param $data
     *
     * 收货系统 检测结果反馈
     *  $data = [
    [
    'goods_no' => '123',
    'check_result' => 'success',//是否合格 fasle/success
    'check_description' => '原因',
    'evaluation_time' => '123123123',//检测时间
    'price' => '342'
    ],
    [
    'goods_no' => '123',
    'check_result' => 'success',//是否合格 fasle/success
    'check_description' => '原因',
    'evaluation_time' => '123123123',//检测时间
    'price' => '21'
    ]
    ];
     */

    public static function checkResult($order_no, $business_key,$data)
    {
        $base_api = config('tripartitle.API_INNER_URL');

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.Return.isQualified',//模拟
            'data' => json_encode(['order'=>$order_no,'business_key'=>$business_key,'data'=>$data])
        ]);

        return $response;
    }


}