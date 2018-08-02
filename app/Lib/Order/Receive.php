<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:50
 */

namespace App\Lib\Order;
use App\Lib\Common\LogApi;
use App\Lib\Curl;
/**
 * Class Delivery
 * 与收发货相关
 */
class Receive
{

    /**
     * @param $order_no
     * $business_key业务类型
     * @param $data
     *
     * 收货系统 检测结果反馈   //废弃
     *  $data = [
    [
    'refund_no' => '123',
    'check_result' => 'success',//是否合格 fasle/success
    'check_description' => '原因',
    'evaluation_time' => '123123123',//检测时间
    'price' => '342'
    ],
    [
    'refund_no' => '123',
    'check_result' => 'success',//是否合格 fasle/success
    'check_description' => '原因',
    'evaluation_time' => '123123123',//检测时间
    'price' => '21'
    ]
    ];
     */

    public static function checkResult(string $order_no, int $business_key,array $data)
    {
        try{
            $base_api = config('ordersystem.ORDER_API');

            $response = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.Return.isQualified',//模拟
                'data' => ['order'=>$order_no,'business_key'=>$business_key,'data'=>$data]
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
     * 退换货 ---取消退换货申请
     * @param $receive_no
     * [
     *  'receive_no' =>''  //收货单编号
     * ]
     * @return bool
     */
    public static function cancelReceive($receive_no)
    {
        if(empty($receive_no)){
            return false;
        }
        try{

            $data = config('tripartite.Interior_Order_Request_data');
            $data['method'] ='warehouse.receive.cancel';
            $data['params'] = [
                'receive_no'=>$receive_no,
            ];
            LogApi::debug("转发参数",$data);
            $baseUrl = config("ordersystem.ORDER_API");
            $info = Curl::post($baseUrl, $data);
            LogApi::debug("转发收发货取消接口",$info);
            $res = json_decode($info);
            if ($res->code != 0) {
                return false;
            }

        } catch (\Exception $e) {
            LogApi::debug($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 退换货 ---收到货通知
     * @param $refund_no     退货单号  string  【必传】
     * @param business_key  业务类型    int   【必传】
     * @param array $userinfo 业务参数        【必传】
     * [
     *       'uid'        =>'',    用户id  int【必传】
     *       'type'       =>'',   请求类型（2前端，1后端） int 【必传】
     *      ‘username’  =>‘’，用户名 string【必传】
     * ]
     * @return bool
     */
    public static function receivedReturn($refund_no,int $business_key,array $userinfo)
    {
        $data = config('tripartite.Interior_Order_Request_data');
        $data['method'] ='api.Return.returnReceive';
        $data['params'] = [
            'refund_no'=>$refund_no,
            'business_key'=>$business_key,
            'userinfo'=>$userinfo,
        ];
        $baseUrl = config("ordersystem.ORDER_API");
        $info = Curl::post($baseUrl, $data);
        LogApi::debug("退换货转发收发货收到货通知接口",$info);
        $res = json_decode($info);
        if ($res->code != 0) {
            throw new \Exception( 'code '.$res->code.':'.$res->msg);
        }

        return true;
    }




}