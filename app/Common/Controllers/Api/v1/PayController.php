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
		
		$str = '{
        "gmt_create":"2018-05-25 22:26:17",
        "charset":"UTF-8",
        "seller_email":"shentiyang@huishoubao.com.cn",
        "gmt_payment":"2018-05-25 22:26:18",
        "notify_time":"2018-05-25 22:29:56",
        "subject":"测试支付",
        "gmt_refund":"2018-05-25 22:29:56.414",
        "sign":"Mew8KyN8zQuR3IEwxpwciQk8Zy1KRXTT48RJmd4cirY33zev+PIh7Bi1+moDm2QvPrEX6mRyDaJA5YzXNKksSPqPsTUqP5+IKWDptTWgPhwJIBsiMenUWxCKMlIwoJ1r+5jGZSVxCdO4cwDDEEQhucQlW3TxAmnUj84Leb6okg/aldKWUJidTQSfi5Xz5/wNZcLbT9xJirwVgpGwlEICgp/7tmh4mErrY9lAx+sgNG2Zrxamlg6epgUfCD8ZEEGRELzAuMq1OVGCfdYil73Qv6Hs2cB0Engl11Ovj75yWECPU5edTPuVylYzHm/RHYoF/5XE8v5pIS+aVreD89td/A==",
        "out_biz_no":"11A52558596154018",
        "buyer_id":"2088502596805705",
        "version":"1.0",
        "notify_id":"5c483f5ee663feca4fb9553d22a2ea5lei",
        "notify_type":"trade_status_sync",
        "out_trade_no":"10A52558371520476",
        "total_amount":"2.00",
        "trade_status":"TRADE_SUCCESS",
        "refund_fee":"1.00",
        "trade_no":"2018052521001004700282271635",
        "auth_app_id":"2017101309291418",
        "buyer_logon_id":"153****1612",
        "app_id":"2017101309291418",
        "sign_type":"RSA2",
        "seller_id":"2088821442906884"
    }';
		$_POST = json_decode($str,true);
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
	
}
