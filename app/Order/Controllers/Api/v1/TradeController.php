<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;


class TradeController extends Controller
{
    protected $orderTrade;

    public function __construct(Service\OrderTrade $orderTrade)
    {
        $this->orderTrade = $orderTrade;
    }

    //支付宝初始化接口
    public function alipayInitialize(Request $request){
        $params =$request->all();
        $params =$params['params'];
        $return_url =$params['return_url'];
        //$order_no =$params['order_no'];
        $order_no ="A509150809460137";
        //判断参数是否设置
        if(empty($return_url)){
            return apiResponse([],ApiStatus::CODE_20001,"回调地址不能为空");
        }
        if(empty($order_no)){
            return apiResponse([],ApiStatus::CODE_20001,"订单编号不能为空");
        }
        $data =[
            'order_no'=>$order_no,
            'return_url'=>$return_url,
        ];

        $b= $this->orderTrade->alipayInitialize($data);
        if($b===false){
            return apiResponse([],ApiStatus::CODE_50004);
        }
        return apiResponse($b,ApiStatus::CODE_0);
        die;


    }
    //支付回调接口
    public function notify(Request $request){
        $params =$request->input();
        $params=[
            'gmt_create' => '2017-11-28 02:58:20',//支付时间
            'trade_status' => 'TRADE_SUCCESS',//支付状态
            'out_trade_no' => '2017112800014',//订单生成支付交易码
            'trade_no' => '2017112821001004700573432203',//返回流水号
        ];
        var_dump("成功更新订单支付状态");die;
        //发送短信
        //发送支付宝推送消息
        //发送邮件 -----begin
//        $data =[
//            'subject'=>'用户已付款',
//            'body'=>'订单编号：'.$order_info['order_no']."联系方式：".$order_info['mobile']." 请联系用户确认租用意向。",
//            'address'=>[
//                ['address' => EmailConfig::Service_Username]
//            ],
//        ];
//
//        $send =EmailConfig::system_send_email($data);
//        if(!$send){
//            Debug::error(Location::L_Trade, "发送邮件失败", $data);
//        }
//
//        //发送邮件------end

    }


}
