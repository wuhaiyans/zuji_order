<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\ApiStatus;
use App\Lib\Curl;
use App\Lib\Order\OrderInfo;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Class Delivery
 * 发货系统
 */
class Delivery
{
    use ValidatesRequests;

    const SESSION_ERR_KEY = 'warehouse.delivery.errors';
    /*
    *
    * 用户换货，发货
    * array(
    * 'order_no'=>'2312123', //必须
    * 'goods_no'=>['sdfsfsdfsd'],//必须
    * 'realname'=>'张三',//可不填
    * 'mobile=>'18588884444',//可不填
    * 'address_info=>'北京昌平某地址',//可不填
    * )
    *
    */
    public  static function createDelivery($params){
        $base_api = config('tripartite.warehouse_api_uri');

        $rules = [
            'order_no' => 'required',
            'goods_no' => 'required'
        ];

        $validator = app('validator')->make($params, $rules);

        if ($validator->fails()) {
            session()->flash(self::SESSION_ERR_KEY, $validator->errors()->first());
            return false;
        }

        $data = [
            'order_no' => $params['order_no'],
            'delivery_detail' => [
                'goods_no' => $params['goods_no']
            ]
        ];

        $postData = array_merge(self::getParams(),[
            'method'=> 'warehouse.delivery.deliveryCreate',//模拟
            'params' => json_encode($data)
        ]);

        $res= Curl::post($base_api, $postData);
        $res = json_decode($res, true);

        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            session()->flash(self::SESSION_ERR_KEY, $res['msg']);
            return false;
        }

        return true;
    }
    /**
     * 订单请求 发货申请
     *
     * @param string $order_no 订单号
     * @return boolean
     */
    public static function apply($order_no)
    {
        $base_api = config('tripartite.warehouse_api_uri');

        $info = self::getOrderDetail($order_no);

        $res= Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.send',//模拟
            'params' => json_encode($info)
        ]);
         return true;
    }

    /**
     * 订单请求 取消发货
     *
     * @param string $order_no 订单号
     */
    public static function cancel($order_no)
    {
        $base_api = config('api.warehouse_api_uri');

        return Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.cancel',//模拟
            'params' => json_encode(['order_no'=>$order_no])
        ]);
    }


    /**
     * 确认收货接口
     * 接收反馈
    *
     * @param string $order_no
    * @param int $role  在 App\Lib\publicInc 中;
     *  const Type_Admin = 1; //管理员
     *  const Type_User = 2;    //用户
     *  const Type_System = 3; // 系统自动化任务
     *  const Type_Store =4;//线下门店
     * @return
        */
    public static function receive($orderNo, $role)
    {
        return \App\Lib\Order\Delivery::receive($orderNo, $role);


    }


    /**
     * Delivery constructor.
     * 发货反馈
     * @param $params array $
        'order_no'=> 订单号  string,
        'good_info'=> 商品信息：goods_id` '商品id',goods_no 商品编号
        e.g: array('order_no'=>'1111','goods_id'=>12,'goods_no'=>'abcd',imei1=>'imei1',imei2=>'imei2',imei3=>'imei3','serial_number'=>'abcd')
     */
    public static function delivery($params)
    {
      return \App\Lib\Order\Delivery::delivery($params);
    }

    /**
     * 根据order_no取发货详细内容
     * 直接调用订单那边提供的方法
     *
     * @param array $order_no 订单号
     */
    public static function getOrderDetail($order_no)
    {
        $info = OrderInfo::getOrderInfo(['order_no'=>$order_no]);
        return $info;
    }




    public static function getParams()
    {
        return [
            'appid'=> 1,
            'version' => 1.0,
        ];
    }

}