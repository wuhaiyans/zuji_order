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
		
		$str = '{"gmt_create":"2018-05-24 21:52:26","charset":"UTF-8","seller_email":"shentiyang@huishoubao.com.cn","gmt_payment":"2018-05-24 21:52:26","notify_time":"2018-05-26 03:29:24","subject":"\u6d4b\u8bd5\u652f\u4ed8","gmt_refund":"2018-05-25 03:03:59.922","sign":"D\/7i8kJ1sEpgxuNABEorqrfClOEwbTA0IU8Vogdv0woJfyo5yMEVXdeseRkMfsOEc\/lkl3107wV2OqhzM8QJ4hmdh24ZzvOcQf+tG4+bqdffjSqT3M+mkNm72ZyDctX9L7rNRSg\/uoSvrCjIbHkTmLAYqTPZnR\/U6+eeuUqBIO8Ecad2IFlQDsE2HWYyLySUrGlioqNefcukOmlMnU5qTzkUnY7BpFZw+zpYQRZXI8KUwhXN4HMcxJ3BxaEcyMhxNSGnrAQNvao22U1JJJYOl2hF90YEni\/yi+rUkB9WMASjOHZZdS+kKADOSYyJUkDAuw+doGyC\/BnX7BkRdvySIA==","out_biz_no":"11A52588636265908","buyer_id":"2088502596805705","version":"1.0","notify_id":"9facebf5e968cc43d6ee7ae230a30b2lei","notify_type":"trade_status_sync","out_trade_no":"10A52469942269732","total_amount":"2.00","trade_status":"TRADE_SUCCESS","refund_fee":"0.54","trade_no":"2018052421001004700275315319","auth_app_id":"2017101309291418","buyer_logon_id":"153****1612","app_id":"2017101309291418","sign_type":"RSA2","seller_id":"2088821442906884"}';
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
    		'payment_no'	=> '10A52684422871571', //支付系统支付码
    		'amount'		=> 2, //支付金额；单位：分
			'refund_back_url'		=> env('APP_URL').'/order/pay/refundNotify',	//【必选】string //退款回调URL
		]);
		var_dump( $info );exit;
	}
	
	
	
	// 测试 支付
	public function test(){
		
		
		$business_type = 1; 
		$business_no = 'FA522834027093802';
		$pay = null;
		try {
			// 查询
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);
			// 取消
			$pay->cancel();
			// 恢复
			$pay->resume();

		} catch (\App\Lib\NotFoundException $exc) {

			// 创建支付
			$pay = \App\Order\Modules\Repository\Pay\PayCreater::createPayment([
				'user_id'		=> '5',
				'businessType'	=> $business_type,
				'businessNo'	=> $business_no,
				
				'paymentAmount' => '0.01',
				'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Jdpay,
				'paymentFenqi'	=> 0,
				
				'withholdChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
				
				'fundauthAmount' => '1.00',
				'fundauthChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
			]);
		} catch (\Exception $exc) {
			exit('error');
		}
		
		try {
			$step = $pay->getCurrentStep();
			// echo '当前阶段：'.$step."\n";
			
			$_params = [
				'name'			=> '测试支付',					//【必选】string 交易名称
				'front_url'		=> env('APP_URL').'/order/pay/testPaymentFront',	//【必选】string 前端回跳地址
			];
			
			$pay->setPaymentAmount(0.02);
			
			$url_info = $pay->getCurrentUrl( \App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
			header( 'Location: '.$url_info['url'] ); 
//			var_dump( $url_info );
			
		} catch (\Exception $exc) {
			echo $exc->getMessage()."\n";
			echo $exc->getTraceAsString();
		}
		
	}
	// 测试 支付前端回跳
	public function testPaymentFront(){
		LogApi::info('支付同步通知', $_GET);
		var_dump( $_GET );exit;
	}
	
	// 测试 解冻
	public function testFundauthUnfreeze(){
		
		try {
			
			$info = \App\Lib\Payment\CommonFundAuthApi::unfreeze([
				'name'			=> '测试退款',			//交易名称
				'out_trade_no' => \createNo(1),		//业务系统交易码
				'fundauth_no'	=> '20A52283846181612', //支付系统授权码
				'amount'		=> 1, //支付金额；单位：分
				'user_id' => '5',
				'back_url'		=> env('APP_URL').'/order/pay/fundautUnfreezeNotify',	//【必选】string //退款回调URL
			]);
			var_dump( $info );exit;
			
		} catch (\App\Lib\ApiException $exc) {
			
			var_dump( $exc->getCode() );
			var_dump( $exc->getMessage() );
			var_dump( $exc->getData() );
			echo $exc->getOriginalValue();exit;
		} catch (\Exception $exc) {
			echo $exc->getTraceAsString();
		}



	}
	
	
}
