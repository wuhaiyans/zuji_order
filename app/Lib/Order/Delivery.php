<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:50
 */

namespace App\Lib\Order;
use App\Lib\Common\LogApi;
use App\Lib\Curl;
use Illuminate\Support\Facades\Log;

/**
 * Class Delivery
 * 与收发货相关
 */
class Delivery
{
    /**
     * 客户收货或系统自动签收会通知到此方法
     * @param string $orderNo
     * @param array $row[
     *      'receive_type'=>签收类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>用户ID（管理员或用户必须）,
     *      'user_name'=>用户名（管理员或用户必须）,
     * ]
     *
     * int receive_type  在 App\Lib\publicInc 中;
     *  const Type_Admin = 1; //管理员
     *  const Type_User = 2;    //用户
     *  const Type_System = 3; // 系统自动化任务
     *  const Type_Store =4;//线下门店
     */

    public static function receive($orderNo,$row)
    {
        try{
            $base_api = config('ordersystem.ORDER_API');
            $params['order_no'] =$orderNo;
            $params['row'] =$row;

            $response = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.order.deliveryReceive',//模拟
                'params' => $params
            ]);
            $res = json_decode($response);
            if ($res->code != 0) {
                return false;
            }
        }catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }
     //申请退货审核通过-》客户发货后，会通知此方法

    public static function user_receive($params)
    {
        $base_api = config('ordersystem.ORDER_API');

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.Return.userReceive',//模拟
            'params' => $params
        ]);

        return $response;

    }

    /**
     * 发货更新请求
     * 判断是订单发货还是换货发货
     * 换货发货新商品反馈到此方法 order_good_extend
     * @param $orderDetail array
     * [
     *  'order_no'=>'',//订单编号
     *  'logistics_id'=>''//物流渠道ID
     *  'logistics_no'=>''//物流单号
     * ]
     * @param $goods_info array 商品信息 【必须】 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     *   [
     *      'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd'
     *   ]
     * ]
     *@param $operatorInfo array 操作人员信息
     * [
     *      'type'=>发货类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,//用户ID
     *      'user_name'=>1,//用户名
     * ]
     * 需要写成curl形式 供发货系统使用
     */
    public static function delivery($orderDetail,$goodsInfo,$operatorInfo)
    {
        $base_api = config('ordersystem.ORDER_API');
        $params['order_info'] =$orderDetail;
        $params['goods_info'] =$goodsInfo;
        $params['operator_info'] =$operatorInfo;
        LogApi::info("发货请求参数url:".$base_api,$params);
        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.order.delivery',//模拟
            'params' => $params
        ]);
        LogApi::info("发货请求返回",$response);

        return $response;


    }
}