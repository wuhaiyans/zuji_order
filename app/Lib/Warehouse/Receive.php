<?php
/**
 * User: wanjinlin
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\Common\LogApi;
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
     * @params  $type
     *   type    =>'' 业务类型: 1：还，2：退，3：换   int  【必传】
     *
     * @params  $order_no
     *  order_no  =>'' 订单编号                        string【必传】
     *
     * @params  $goods_info
     * [
     *      'serial_no'  => '',   序列号      string    【可传】
     *      'goods_no'   => ‘’, 商品编号    string    【必传】
     *      'refund_no'  => '',   退换货单号  string    【可传】
     *      'goods_name' => '',   商品名称    string    【必传】
     *      'quantity'   => '',    商品数量    int      【可传】
     *      'imei'       => ''     商品imei    string   【可传】
     *      'business_no'  => '',   退换货单号  string  【可传】
     *      'zuqi'  => '',   租期  string  【必传】
     *      'zuqi_type'  => '',   租期类型  string  【必传】
     *      'channel_id'  => '',   渠道  string  【必传】
     *      'appid'  => '',      string  【必传】
     * ]
     * @params  $data
     * [
     *   'logistics_id'=>'',  物流id         string  【可传】
     *   'logistics_no'=>'',  物流编号       string  【可传】
     *   'business_key'=>'',  业务类型       int     【必传】
     *   'customer'    =>'',   用户名        string  【必传】
     *   'customer_mobile'=>'',用户手机号    int     【必传】
     *   'customer_address'=>'',用户地址     string 【必传】
     *
     * ]
     * @return  bool |array
     */
    public static function create(string $order_no, int $type, array $goods_info,array $data)
    {
        $receive_detail = [];


        $orderInfo = \App\Order\Modules\Repository\OrderRepository::getInfoById($order_no);
        if (!$orderInfo) {
            \App\Lib\Common\LogApi::debug('[GiveBackCreateReceive]订单不存在'.$order_no);
            return false;
        }

        $logistics_id = isset($data['logistics_id']) ? $data['logistics_id'] : 0;
        $logistics_no = isset($data['logistics_no']) ? $data['logistics_no'] : 0;
//
//        $detail = $data['receive_detail'];

        if (is_array($goods_info)) {
            foreach ($goods_info as $d) {
                if (!$d['goods_no']) continue;
                
                $receive_detail[] = [
                    'serial_no'     => isset($d['serial_no']) ? $d['serial_no'] : '',//可以不传
                    'goods_no'      => $d['goods_no'],
                    'refund_no'     => isset($d['refund_no'])? $d['refund_no'] : '',
                    'goods_name'    => $d['goods_name'],
                    'quantity'      => isset($d['quantity']) ? $d['quantity'] : 1,
                    'imei'          => isset($d['imei']) ? $d['imei'] : '',
                    'specs'         => $d['specs'] ? $d['specs'] : '',
                    'goods_thumb'   => $d['goods_thumb'] ? $d['goods_thumb'] : '',
                    'zujin'         => $d['zujin'] ? $d['zujin'] : 0,
                ];
            }
        }
        //转发给创建收货单接口的参数
        $result = [
            'order_no'          => $order_no,
            'receive_detail'    => $receive_detail,
            'logistics_id'      => $logistics_id,
            'logistics_no'      => $logistics_no,
            'type'              => $type,
            'business_key'      => $data['business_key'],
            'customer'          => $data['customer'],
            'customer_mobile'   => $data['customer_mobile'],
            'customer_address'  => $data['customer_address'],
            'channel_id'        => $data['channel_id'],
            'appid'             => $data['appid'],
            'order_type'        => $orderInfo['order_type'],
            'business_no'       => isset($goods_info[0]['business_no'])?$goods_info[0]['business_no']:'',
        ];

        $base_api = config('tripartite.warehouse_api_uri');

        $res = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.receive.create',//通知收发货系统的创建待收货单接口
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
     *@params $params
     *[
     * 'receive_no'   =>'' 收货单编号  string  【必传】
     * 'logistics_id' =>'' 物流id      string  【必传】
     * 'logistics_no' =>'' 物流编号    string  【必传】
     * ]
     * @return bool
     *
     */
    public static function updateLogistics(array $params){

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
                'method'=> 'warehouse.receive.logistics',//通知收发货系统更新物流信息接口
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
     * 线下门店端 监测不合格 获取支付信息
     *@params $params
     *[
     * 'goods_no'   =>'' 商品编号  string  【必传】
     * ]
     * @return status true or false
     *
     */
    public static function getEvaluationPayInfo(array $params){

        $data = config('tripartite.Interior_Order_Request_data');
        $data['method'] ='api.giveback.getEvaluationPayInfo';
        $data['params'] = [
            'goods_no'=>$params['goods_no']
        ];
        $baseUrl = config("ordersystem.ORDER_API");
        $info = Curl::post($baseUrl, $data);
        LogApi::info("Receive_getEvaluationPayInfo_退换货转发收发货收到货通知接口",$info);
        $res = json_decode($info,true);
        if ($res['code'] != 0) {
            throw new \Exception( 'code '.$res['code'].':'.$res['msg']);
        }

        return $res['data']['status'];

    }

    /**
     * @param $order_no
     * $business_key业务类型
     * @param $data
     *
     * 收货系统 检测结果反馈
     *  $data = [
    [
        'goods_no'          => '123',       商品编号
        'check_result'      => 'success', //是否合格 fasle/success
        'check_description' => '原因',
        'evaluation_time'   => '123123123',//检测时间
        'price'             => '342'
    ],
    [
        'goods_no'          => '123',       商品编号
        'check_result'      => 'success', //是否合格 fasle/success
        'check_description' => '原因',
        'evaluation_time'   => '123123123',//检测时间
        'price'             => '342'
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
     *      'amount' => '',//需要单独支付金额【可选】【押金不够赔偿时必有】<br/>
     *  ],
     *  [
     *      'goods_no' => '',//商品编号<br/>
     *      'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
     *      'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
     *      'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
     *      'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
     *      'amount' => '',//需要单独支付金额【可选】【押金不够赔偿时必有】<br/>
     *  ],
     * ]
     * @param string $business_key  业务类型
     * @param array $userInfo       用户信息
     * [
     *       'uid'        =>'',【请求参数】 用户id
     *       'type'       =>'',【请求参数】 请求类型（2前端，1后端）
     *      ‘username’  =>‘’，【请求参数】 用户名
     * ]
     */
    public static function checkItemsResult(array $params,int $business_key=0,array $userInfo=[])
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
    public static function receive(string $receive_no,array $userinfo)
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

        LogApi::info('order_receive_info_send',['result'=>$result,'userinfo'=>$userinfo,'refund_no'=>$refund_no]);
        if($receive->business_key == OrderStatus::BUSINESS_GIVEBACK){
            Giveback::confirmDelivery($result,$userinfo);
        }elseif ($receive->business_key == OrderStatus::BUSINESS_RETURN || $receive->business_key == OrderStatus::BUSINESS_BARTER){
            \App\Lib\Order\Receive::receivedReturn($refund_no,$receive->business_key,$userinfo);
        }else{
            Log::error(__METHOD__ . '收货签收失败');
            throw new \Exception( 'business_key 业务类型错误');
        }

    }

}