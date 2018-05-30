<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\Curl;
use App\Lib\Order\Giveback;
use App\Warehouse\Models\ReceiveGoods;
use Illuminate\Support\Facades\Log;

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
     * order_no
     * data=[
     *      [
     *          serial_no 【可选】
     *          goods_no 【必须】 //商品编号
     *          quantity 【可选】 //商品数量
     *          imei  【可以没有】
     *      ]
     *  ]
     */
    public static function create($order_no, $type, $goods_info)
    {
        $receive_detail = [];

//        $logistics_id = isset($data['logistics_id']) ? $data['logistics_id'] : 0;
//        $logistics_no = isset($data['logistics_no']) ? $data['logistics_no'] : 0;
//
//        $detail = $data['receive_detail'];

        if (is_array($goods_info)) {
            foreach ($goods_info as $d) {
                if (!$d['goods_no']) continue;
                
                $receive_detail[] = [
                    'serial_no' => isset($d['serial_no']) ? $d['serial_no'] : '',//可以不传
                    'goods_no'  => $d['goods_no'],
                    'quantity'  => isset($d['quantity']) ? $d['quantity'] : 1,
                    'imei'      => isset($d['imei']) ? $d['imei'] : ''
                ];
            }
        }

        $result = [
            'order_no' => $order_no,
            'receive_detail' => $receive_detail,
//            'logistics_id' => $logistics_id,
//            'logistics_no' => $logistics_no,
            'type' => $type
        ];

        $base_api = config('tripartite.warehouse_api_uri');

        $res = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.receive.create',//模拟
            'params' => json_encode($result)
        ]);
		return $res;

    }

    /**
     * 更新物流信息
     *
     * @param $params string 订单信息 【必须】
        $params = [
            'order_no' => '123444',
            'logistics_id' => 1,
            'logistics_no' => 123,
            'goods_info' => [
                [
                'goods_no' => 123, 'imei1'=>1234, 'imei2'=>4567
                ],
                [
                'goods_no' => 123, 'imei1'=>1234, 'imei2'=>4567
                ],
                [
                'goods_no' => 123, 'imei1'=>1234, 'imei2'=>4567
                ],
            ]
        ];
     *
     * @return bool
     *
     */
    public function updateLogistics($params){

        try {
            $result = [
                'order_no' => $params['order_no'],
                'logistics_id' => $params['logistics_id'],
                'logistics_no' => $params['logistics_no'],
                'goods_no' => $params['goods_info'][0]['goods_no']
            ];

            $base_api = config('tripartite.warehouse_api_uri');

            $res = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'warehouse.receive.create',//模拟
                'params' => json_encode($result)
            ]);
            return $res;


        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

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

//    public static function checkResult($order_no, $business_key,$data)
//    {
//        $base_api = config('tripartitle.API_INNER_URL');
//
//        $response = Curl::post($base_api, [
//            'appid'=> 1,
//            'version' => 1.0,
//            'method'=> 'api.Return.isQualified',//模拟
//            'data' => json_encode(['order'=>$order_no,'business_key'=>$business_key,'data'=>$data])
//        ]);
//
//        return $response;
//    }


    /**
     * 检查项反馈
     *
     * [
     *  [
     *      'goods_no' => '',//商品编号<br/>
     *      'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
     *      'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
     *      'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
     *      'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
     *  ],
     *  [
     *      'goods_no' => '',//商品编号<br/>
     *      'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
     *      'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
     *      'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
     *      'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
     *  ],
     * ]
     *
     */
    public static function checkItemsResult($params)
    {
        if (!$params || !is_array($params)) return ;

        foreach ($params as $v) {
            $result[] = [
                'goods_no' => $v['goods_no'],
                'evaluation_status' => $v['check_result'],
                'evaluation_time' => $v['create_time'],
                'evaluation_remark' => $v['check_description'],
                'compensate_amount' => $v['check_price']
            ];
        }
        try {
            Giveback::confirmEvaluation($result);
        } catch (\Exception $e) {
            Log::error(__METHOD__ . '检测项反馈失败');
        }

    }


    /**
     * 收到货通知
     *
     * [
     *      ['goods_no'=>123],
     *      ['goods_no'=>123],
     * ]
     */
    public static function receive($receive_no)
    {
        if (!$receive_no) return;

        $receive = \App\Warehouse\Models\Receive::find($receive_no);
        $goods = $receive->goods;

        $result = [];

        foreach ($goods as $g) {
            if ($g->status != ReceiveGoods::STATUS_ALL_RECEIVE) continue;
            $result[] = [
                'goods_no' => $g->goods_no
            ];
        }

        try {
            Giveback::confirmDelivery($result);
        } catch (\Exception $e) {
            Log::error(__METHOD__ . '收货反馈失败');
        }

    }

}