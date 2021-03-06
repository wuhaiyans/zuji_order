<?php
/**
 * User: jinlin
 * Date: 2018/5/7
 * Time: 17:52
 */


namespace App\Lib\Warehouse;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Curl;
use App\Lib\Order\OrderInfo;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

/**
 * Class Delivery
 * 发货系统
 */
class Delivery
{
    use ValidatesRequests;

    const SESSION_ERR_KEY = 'warehouse.delivery.errors';

    /*
    * 用户换货，发货
    * array(
    * 'order_no'=>'2312123', //必须
    * 'goods'=>[ //必须
    *  [
    *      'goods_no' => 'abcde123xx1'//必须
    * ],
    *  [
    *      'goods_no' => 'abcde123xx1'
    * ],
    * ],//必须
    * 'realname'=>'张三',//可不填
    * 'mobile=>'18588884444',//可不填
    * 'address_info=>'北京昌平某地址',//可不填
    * )
    *
    *
    * 例:Delivery::createDelivery([
        'order_no'=>123333,
        'realname' => '张三',
        'mobile' => '手机号',
        'address_info' => '收货地址',
        'goods'=> [
            ['goods_no'=> 123],
            ['goods_no'=> 456]
    ]]);
    *
    *
    */
    public  static function createDelivery($params){
		// liuhongxing 2018-06-29 修改
        //$base_api = config('tripartite.warehouse_api_uri');
		$base_api = env('WAREHOUSE_API');
		
        $rules = [
            'order_no' => 'required',
            'goods' => 'required'
        ];

        $validator = app('validator')->make($params, $rules);

        if ($validator->fails()) {
            session()->flash(self::SESSION_ERR_KEY, $validator->errors()->first());
            return false;
        }

        $data = [
            'order_no' => $params['order_no'],
            'customer' => isset($params['realname']) ? $params['realname'] : '',
            'customer_mobile' => isset($params['mobile']) ? $params['mobile'] : '',
            'customer_address' => isset($params['address_info']) ? $params['address_info'] : '',
            'delivery_detail' => $params['goods']
        ];

        $postData = array_merge(self::getParams(),[
            'method'=> 'warehouse.delivery.deliveryCreate',//模拟
            'params' => json_encode($data)
        ]);

        $res= Curl::postArray($base_api, $postData);

        $res = json_decode($res, true);

        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            session()->flash(self::SESSION_ERR_KEY, $res['msg']);
            return false;
        }
        return true;
    }


    /**
     * 订单请求(确认订单) 发货申请
     * @author wuhaiyan
     * @param $orderInfo 订单信息
     * $orderInfo =[
     *  'order_no'  => '', //【必须】string 订单编号
     *  'business_key'=>'',//【必须】string 业务编码
     *  'business_no'=>'',//【必须】string 业务编号
     *  'channel_id'=>'',//【必须】int 所属渠道ID
     *  'order_type'=>'',//【必须】int 订单类型
     *  'appid'=>'',//【必须】string 来源appid
     *  'predict_delivery_time'=>'',//【必须】string 预计发货时间
     *  'customer'  => '',//【必须】string 收货人姓名
     *  'customer_mobile' => '',//【必须】string 收货人手机号
     *  'customer_address' = '',//【必须】string 收货人地址
     * ]
     * $param $goodsInfo 订单商品表的信息
     * @return boolean
     */
    public static function apply($orderInfo,$goodsInfo)
    {
		// liuhongxing 2018-06-29 修改
        //$base_api = config('tripartite.warehouse_api_uri');
		$base_api = env('WAREHOUSE_API');
        $result = [
            'order_no'  => $orderInfo['order_no'],
            'business_key'=>$orderInfo['business_key'],
            'business_no'=>$orderInfo['business_no'],
            'order_type'=>$orderInfo['order_type'],
            'channel_id'=>$orderInfo['channel_id'],
            'appid'=>$orderInfo['appid'],
            'predict_delivery_time'=>$orderInfo['predict_delivery_time'],
            'customer'  => $orderInfo['name'],
            'customer_mobile'    => $orderInfo['consignee_mobile'],
            'customer_address' => $orderInfo['address_info'],
            'delivery_detail' => $goodsInfo
        ];
        //LogApi::info("发货申请参数",$result);

		$params = [
            'method'=> 'warehouse.delivery.deliveryCreate',//模拟
            'params' => json_encode($result)
        ];
        $res= Curl::post( $base_api, array_merge(self::getParams(), $params) );
        //LogApi::info("发货申请回执",$res);
		
        $res = json_decode($res, true);;

		\App\Lib\Common\LogApi::info( 'deliveryApply', [
			'url' => $base_api,
			'request' => $params,
			'response' => $res,
		] );

        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            session()->flash(self::SESSION_ERR_KEY, $res['msg']);
            return false;
        }

        return true;
    }

    /**
     * @param $order_no
     * @return array|bool
     *
     * 为创建发货申请提供数据源
     */
    protected static function getOrderInfo($order_no)
    {
        $info = OrderInfo::getOrderInfo(['order_no'=>$order_no]);
        $info = json_decode($info, true);
        $info = $info['data'];

        if (!$info || !isset( $info['order_info']) || !isset( $info['goods_info'])) {
            return false;
        }
        $order_info = $info['order_info'];
        $goods_list = $info['goods_info'];

        $result = [
            'order_no'  => $order_no,
            'realname'  => $order_info['name'],
            'mobile'    => $order_info['user_mobile'],
            'address_info' => $order_info['address_info'],
        ];

        foreach ($goods_list as $goods) {
            $g[] = [
                'goods_no' => $goods['goods_no'],
                'goods_name' => $goods['goods_name'],
                'quantity'  => $goods['quantity']
            ];
        }

        $result['delivery_detail'] = $g;

        return $result;
    }

    /**
     * 订单请求 取消发货
     *
     * @param string $order_no 订单号
     */
    public static function cancel($order_no)
    {
		// liuhongxing 2018-06-29 修改
        $base_api = config('tripartite.warehouse_api_uri');

        $res = Curl::post($base_api, array_merge(self::getParams(), [
            'method'=> 'warehouse.delivery.cancel',//模拟
            'params' => json_encode(['order_no'=>$order_no])
        ]));

        $res = json_decode($res, true);

        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            session()->flash(self::SESSION_ERR_KEY, $res['msg']);
            return false;
        }

        return true;
    }

    /**
     * 取消发货后,退货退款审核未通过,继续发货
     *
     * @param string $order_no 订单号
     * @param int $status 4=待发货,5已发货待签收
     */
    public static function auditFailed($order_no,$status=4)
    {
        $base_api = config('tripartite.warehouse_api_uri');

        $res = Curl::post($base_api, array_merge(self::getParams(), [
            'method'=> 'warehouse.delivery.auditFailed',//模拟
            'params' => json_encode(['order_no'=>$order_no,'status'=>$status])
        ]));

        $res = json_decode($res, true);

        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            session()->flash(self::SESSION_ERR_KEY, $res['msg']);
            return false;
        }

        return true;
    }

    /**
     * 订单请求 确认收货
     * @author wuhaiyan
     * @param $params
     * [
     *      'order_no'=>'', //【必须】string 订单编号
     *      'receive_type'=>''//【必须】 类型：int  必有字段  备注：签收类型1管理员，2用户,3系统，4线下
     *      'user_id'=>'', //【必须】 类型：int 操作人员id
     *      'user_name'=>'' //【必须】 类型：String 操作人员姓名
     * ]
     * @return boolean
     */
    public static function orderReceive($params)
    {
		// liuhongxing 2018-06-29 修改
        //$base_api = config('tripartite.warehouse_api_uri');
		$base_api = env('WAREHOUSE_API');

        $res= Curl::post($base_api, array_merge(self::getParams(), [
            'method'=> 'warehouse.delivery.receive',//模拟
            'params' => json_encode($params)
        ]));

        $res = json_decode($res, true);


        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            LogApi::info("收发货系统返回",$res);
            session()->flash(self::SESSION_ERR_KEY, $res['msg']);
            return false;
        }

        return true;
    }
//
//    /**
//     * ok
//     * 确认收货接口  --------废弃
//     * 接收反馈
//     *
//     * @param string $order_no
//     * @param array $row[
//     *      'receive_type'=>签收类型:1管理员，2用户,3系统，4线下,
//     *      'user_id'=>用户ID（管理员或用户必须）,
//     *      'user_name'=>用户名（管理员或用户必须）,
//     * ]
//     *
//     * int receive_type  在 App\Lib\publicInc 中;
//     *  const Type_Admin = 1; //管理员
//     *  const Type_User = 2;    //用户
//     *  const Type_System = 3; // 系统自动化任务
//     *  const Type_Store =4;//线下门店
//     * @return
//     */
//    public static function receive($orderNo, $row)
//    {
//        $row['receive_type'] =5;
//        $response = \App\Lib\Order\Delivery::receive($orderNo, $row);
//        $response =json_decode($response,true);
//        if($response['code']!=ApiStatus::CODE_0){
//            throw new \Exception(ApiStatus::$errCodes[$response['code']]);
//        }
//        return $response;
//    }



    /**
     * 发货反馈 ok
     * @param $orderDetail array
     * [
     *  'order_no'=>'',//【必须】 类型：String 订单编号
     *  'logistics_id'=>'',//【必须】 类型：String 物流渠道ID
     *  'logistics_no'=>'',//【必须】 类型：String 物流单号
     *  'logistics_note'=>'',//【非必须】 类型：String 备注
     *  'return_address_type'=>false,//【非必须】 类型：bool 回寄地址标识(true修改,false不修改)
     *  'return_address_value'=>'',//【非必须】 类型：String 回寄地址
     *  'return_name'=>'',//【非必须】 类型：String 回寄姓名
     *  'return_phone'=>'',//【非必须】 类型：String 回寄电话
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
     * @param $operatorInfo array 操作人员信息
     * [
     *      'type'=>发货类型:1管理员，2用户,3系统，4线下,
     *      'user_id'=>1,//用户ID
     *      'user_name'=>1,//用户名
     * ]
     * @return string
     *
     *
     */
    public static function delivery($orderDetail, $goods_info, $operatorInfo)
    {

      $response =\App\Lib\Order\Delivery::delivery($orderDetail, $goods_info,$operatorInfo);
      LogApi::info("OrderDelivery-request:".$orderDetail['order_no'],[
          'order_info'=>$orderDetail,
          'goods_info'=>$goods_info,
          'operator_info'=>$operatorInfo,
      ]);
      $response =json_decode($response,true);
      if($response['code']!=ApiStatus::CODE_0){
          LogApi::error("OrderDelivery-Request:",[
              'order_info'=>$orderDetail,
              'goods_info'=>$goods_info,
              'operator_info'=>$operatorInfo,
          ]);
          session()->flash(self::SESSION_ERR_KEY, $response['msg']);
          LogApi::error("OrderDelivery-Response:",$response);
          return false;
      }
      LogApi::info("OrderDelivery-request:".$orderDetail['order_no'],$response);
      return true;
    }

    /**
     * 获取订单发货状态
     * @params $order_no
     * @return string
     */
    public static function getDeliveryInfo($order_no){
        $base_api = env('WAREHOUSE_API');
        $params['order_no'] =$order_no;
        LogApi::info("getDeliveryInfo url:".$base_api,$params);
        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'warehouse.delivery.getStatus',//模拟
            'params' => $params
        ]);
        LogApi::info("getDeliveryInfo 请求返回",$response);

        return $response;
    }


    public static function getParams()
    {
        return [
            'appid'=> 1,
            'version' => 1.0,
        ];
    }

}