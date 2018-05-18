<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;
use App\Lib\Common\LogApi;

/**
 * 支付控制器
 */
class PayController extends Controller
{
    protected $orderTrade;

    public function __construct(Service\OrderTrade $orderTrade)
    {
        $this->orderTrade = $orderTrade;
    }

    /**
	 * 通用支付入口，获取支付链接地址
	 * @param Request $request
	 * [
	 *		'payment_no' => '',	// 支付码
	 * ]
	 * @return type
	 */
    public function getPaymentUrl(Request $request){

		$params = $request->all()['params'];
		// 
		if( empty($params['payment_no']) ){
			return apiResponse( [],ApiStatus::CODE_30900,'参数错误[payment_no]');
		}
		$payment_no = $params['payment_no'];
		
		$payModel = \App\Order\Models\OrderPayModel::where('payment_no','=',$payment_no)->first();
		if( !$payModel ){
			return apiResponse( [],ApiStatus::CODE_30900,'支付单未识别');
		}
		$pay = new \App\Order\Modules\Repository\Pay\Pay( $payModel->toArray() );
		LogApi::debug('[直接支付环节]支付链接',$pay);
		
		// 是否可以支付
		if( !$pay->needPayment() ){
			return apiResponse( [],ApiStatus::CODE_30900,'禁止支付');
		}
		
		// 创建url地址
		$_params = [
	 		'out_no' => $pay->getPaymentNo(),
	 		'amount' => $pay->getPaymentAmount()*100, // 元转换成分
	 		'fenqi' => $pay->getPaymentFenqi(),
	 		'name' => 'Xxxx',
	 		'back_url' => 'https://abc.com',
	 		'front_url' => 'https://abc.com',
	 		'user_id' => '5',
		];
		$data = \App\Lib\Payment\AlipayApi::getUrl( $_params );
		
		if( !$data ){
			LogApi::error(ApiStatus::CODE_30901,[
				'msg' => \App\Lib\Payment\AlipayApi::getError(),
				'params' => $_params,
			]);
			return apiResponse( [], ApiStatus::CODE_30901,'服务器忙，稍候重试');
		}
		
        return apiResponse( $data,ApiStatus::CODE_0);
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
