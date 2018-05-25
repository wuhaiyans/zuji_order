<?php

namespace App\Common\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Curl;

/**
 * 支付控制器
 */
class PayController extends Controller
{

    public function __construct()
    {
    }
	
	// 模拟支付成功异步通知
	public function alipayPaymentNotify(){
		
		$str = '{"gmt_create":"2018-05-26 01:49:02","charset":"UTF-8","seller_email":"shentiyang@huishoubao.com.cn","subject":"\u6d4b\u8bd5\u652f\u4ed8","sign":"XBJVL533mOia13bEz7\/RqQPvu6O1UIUi+ysMhqJyzxKb3bdTg2uCaSKtVYoxwOZbQXDjfUJkFlkbaOfPcS+UgBHwRF3CmNMs+MKEksQzHu+hTbHOAVJXEVSvEfx7TPz+ZDxg9str40juxrK0sNjdTGxJN\/vbpsMu9pnDaIfpP5fgraIp6vA9z5ibrF14AinhR+Ie16PhqXmHiEPox5LFUIQVzmatzNU0V4Yn69BzeYqRijtT2G76eEjKn9fyjEzW78dyAdyvL0mGkqGyDK0KsTnIKASev3bHwteTMksCBwH\/YyGbQ3C+jKttH37KIAkjb8RWKDjkJfQeZSwUJ6b0Og==","buyer_id":"2088502596805705","invoice_amount":"0.02","notify_id":"d9a50205698a78d08da7b261d4eb89blei","fund_bill_list":"[{\"amount\":\"0.02\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"0.02","buyer_pay_amount":"0.02","app_id":"2017101309291418","sign_type":"RSA2","seller_id":"2088821442906884","gmt_payment":"2018-05-26 01:49:02","notify_time":"2018-05-26 01:49:02","version":"1.0","out_trade_no":"10A52670538684219","total_amount":"0.02","trade_no":"2018052621001004700281103687","auth_app_id":"2017101309291418","buyer_logon_id":"153****1612","point_amount":"0.00"}';
		$_POST = json_decode($str,true);
		var_dump( $_POST );
		$url = 'https://dev-pay-zuji.huishoubao.com/alipay/Payment/payNotify';
		$res = Curl::post($url, $_POST);
		
		if( empty($res) ){
			echo 'error:'.Curl::getError();exit;
		}
		$arr = json_decode( $res, true );
		if( $arr ){
			var_export( $arr );exit;
		}
		echo $res;exit;
		
	}
	
	
	// 测试 退款
	public function testAlipayRefund(){
		
		$info = \App\Lib\Payment\CommonRefundApi::apply([
    		'name'			=> '测试退款',			//交易名称
    		'out_refund_no' => \createNo(1),		//业务系统退款码
    		'payment_no'	=> '10A52670538684219', //支付系统支付码
    		'amount'		=> 100, //支付金额；单位：分
			'refund_back_url'		=> env('APP_URL').'/order/pay/refundNotify',	//【必选】string //退款回调URL
		]);
		var_dump( $info );exit;
	}
	
	
	
}
