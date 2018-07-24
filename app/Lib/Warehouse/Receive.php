<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\Curl;
use App\Lib\Order\Giveback;
use App\Lib\Order\ReturnGoods;
use App\Order\Modules\Inc\OrderStatus;
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
     * type 类型: 1：还，2：退，3：换
     *
     * $goods_info[
     *      refund_no
     * ]
     *
     *
     */
    public static function create($order_no, $type, $goods_info,$data)
    {
        $receive_detail = [];

        $logistics_id = isset($data['logistics_id']) ? $data['logistics_id'] : 0;
        $logistics_no = isset($data['logistics_no']) ? $data['logistics_no'] : 0;
//
//        $detail = $data['receive_detail'];

        if (is_array($goods_info)) {
            foreach ($goods_info as $d) {
                if (!$d['goods_no']) continue;
                
                $receive_detail[] = [
                    'serial_no' => isset($d['serial_no']) ? $d['serial_no'] : '',//可以不传
                    'goods_no'  => $d['goods_no'],
                    'refund_no'  => isset($d['refund_no'])? $d['refund_no'] : '',
                    'goods_name'  => $d['goods_name'],
                    'quantity'  => isset($d['quantity']) ? $d['quantity'] : 1,
                    'imei'      => isset($d['imei']) ? $d['imei'] : ''
                ];
            }
        }

        $result = [
            'order_no' => $order_no,
            'receive_detail' => $receive_detail,
            'logistics_id' => $logistics_id,
            'logistics_no' => $logistics_no,
            'type' => $type,
            'business_key' => $data['business_key'],
            'customer' => $data['customer'],
            'customer_mobile' => $data['customer_mobile'],
            'customer_address' => $data['customer_address'],
            'business_no'      =>isset($goods_info[0]['business_no'])?$goods_info[0]['business_no']:'',
        ];

        $base_api = config('tripartite.warehouse_api_uri');

        $res = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.receive.create',//模拟
            'params' => json_encode($result)
        ]);
		\App\Lib\Common\LogApi::debug('申请收货',[
			'url' => $base_api,
			'request' => $result,
			'response' => $res,
		]);

        $obj = json_decode($res, true);

        if ($obj['code'] != 0 || !isset($obj['data']['receive_no'])) {
            return false;
        }

		return $obj['data']['receive_no'];

    }

    /**
     * 更新物流信息
     *
     *
     * @return bool
     *
     * $params = $receive_no,$logistics_id,$logistics_no
     */
    public static function updateLogistics($params){

        try {
            $result = [
                'receive_no' => $params['receive_no'],
                'logistics_id' => $params['logistics_id'],
                'logistics_no' => $params['logistics_no'],
            ];

            $base_api = config('tripartite.warehouse_api_uri');

            $res = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'warehouse.receive.logistics',//模拟
                'params' => json_encode($result)
            ]);

            $res = json_decode($res);
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

   /* public static function checkResult($order_no, $business_key,$data)
    {
        try{
            $base_api = config('ordersystem.ORDER_API');
            $response = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.Return.isQualified',//模拟
                'data' => json_encode(['order'=>$order_no,'business_key'=>$business_key,'data'=>$data])
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

    }*/


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
     * @param string $business_key  业务类型
     * @param array $userInfo       用户信息
     * [
     *       'uid'        =>'',【请求参数】 用户id
     *       'type'       =>'',【请求参数】 请求类型（2前端，1后端）
     *      ‘username’  =>‘’，【请求参数】 用户名
     * ]
     *
     */
    public static function checkItemsResult($params,$business_key=0,$userInfo=[])
    {
        if (!$params || !is_array($params)) return ;

        foreach ($params as $v) {
//            $result[] = [
//                'goods_no' => $v['goods_no'],
//                'evaluation_status' => $v['check_result'],
//                'evaluation_time' => $v['create_time'],
//                'evaluation_remark' => $v['check_description'],
//                'compensate_amount' => $v['check_price']
//            ];
            $result[] = $v;
        }

        try {
           if($business_key == OrderStatus::BUSINESS_GIVEBACK){
               Giveback::confirmEvaluationArr($result,$userInfo);
           }elseif ($business_key == OrderStatus::BUSINESS_RETURN || $business_key == OrderStatus::BUSINESS_BARTER){
               ReturnGoods::checkResult($result,$business_key,$userInfo);
           }

        } catch (\Exception $e) {
            Log::error(__METHOD__ . '检测项反馈失败');
            throw new \Exception( $e->getMessage());
		}

    }


    /**
     * 收到货通知(还机和退换类型)
     *
     * [
     *      ['goods_no'=>123],
     *      ['goods_no'=>123],
     * ]
     */
    public static function receive($receive_no,$userinfo)
    {
        if (!$receive_no) return;

        $receive = \App\Warehouse\Models\Receive::find($receive_no);
        $goods = $receive->goods;
        $result = [];
        $refund_no = [];
        foreach ($goods as $g) {
            if ($g->status == ReceiveGoods::STATUS_ALL_RECEIVE) continue;
            $result[] = [
                'goods_no' => $g->goods_no
            ];
            //退换货使用(支持多商品)
            $refund_no[] = [
                'refund_no'=>($g->refund_no?$g->refund_no:''),
                'order_no'=>$receive->order_no,
                'goods_no'=>$g->goods_no
            ];
        }

        if($receive->business_key == OrderStatus::BUSINESS_GIVEBACK){
            Giveback::confirmDelivery($result,$userinfo);
        }elseif ($receive->business_key == OrderStatus::BUSINESS_RETURN || $receive->business_key == OrderStatus::BUSINESS_BARTER){
            return [$refund_no,$receive->business_key,$userinfo];
            \App\Lib\Order\Receive::receivedReturn($refund_no,$receive->business_key,$userinfo);
        }else{
            Log::error(__METHOD__ . '收货签收失败');
            throw new \Exception( 'business_key 业务类型错误');
        }

    }

}