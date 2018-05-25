<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:50
 */

namespace App\Lib\Order;
use App\Lib\Curl;

/**
 * Class Delivery
 * 与收发货相关
 */
class Delivery
{
    /**
     * 客户收货或系统自动签收会通知到此方法
     * @param string $orderNo
     * @param int $role  在 App\Lib\publicInc 中;
     *  const Type_Admin = 1; //管理员
     *  const Type_User = 2;    //用户
     *  const Type_System = 3; // 系统自动化任务
     *  const Type_Store =4;//线下门店
     */

    public static function receive($orderNo,$role)
    {
        $base_api = config('tripartitle.API_INNER_URL');
        $params['order_no'] =$orderNo;
        $params['role'] =$role;

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.delivery.receive',//模拟
            'data' => json_encode(['params'=>$params])
        ]);

        return $response;

    }
     //申请退货审核通过-》客户发货后，会通知此方法

    public static function user_receive($params)
    {
        $base_api = config('tripartitle.API_INNER_URL');

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.Return.userReceive',//模拟
            'data' => json_encode(['params'=>$params])
        ]);

        return $response;

    }

    /**
     * 换货发货新商品反馈到此方法 order_good_extend
     * @param $param :array[
                         'order_no'=> 订单号  string,
                         'good_info'=> 商品信息：good_id` '商品id',good_no 商品编号，ime号 array（'ime1','ime2'）
                         e.g: array('order_no'=>'1111','good_id'=>12,'good_no'=>'abcd',ime1=>'ime1',ime2=>'ime2'),'serial_number'=>'abcd')
     *
     * ]
     *
     * 需要写成curl形式 供发货系统使用
     */
    public static function delivery($params)
    {


        $base_api = config('tripartitle.API_INNER_URL');

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.Return.createChange',//模拟
            'data' => json_encode(['params'=>$params])
        ]);

        return $response;


    }
}