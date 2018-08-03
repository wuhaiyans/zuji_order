<?php
namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Service\OrderNotice;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;

class TestController extends Controller
{
	public function sendSms() {


		$dataSms = [
			'a' =>'1',
		];
		$a = \App\Lib\Common\SmsApi::sendMessage('13654565804', 'SMS_113461164  ', $dataSms);
		v($a);
//		//发送短信
//		$notice = new \App\Order\Modules\Service\OrderNotice(
//			\App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK,
//			'GA71199188045622',
//			'GivebackEvaNoWitYesEnoNo',
//			['amount'=>1]);
//		$notice->notify();



//		$id = 1;
//		$a  = \App\Order\Modules\Service\OrderWithhold::instalment_withhold($id);
//		v($a);

//		//发送短信通知 支付宝内部通知
//		$notice = new \App\Order\Modules\Service\OrderNotice(
//			\App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
//			'FA70200698030574',
//			"InstalmentWithhold");
//
//		$notice->notify();


//		$a = new \App\Order\Controllers\Api\v1\WithholdController();
//		$b = $a->crontab_createpay();
//		v($b);

//		$data = [
//			'payment_no'    => 'required',
//			'out_no'        => 'FA62602377858037',
//			'status'        => 'success',
//			'reason'        => '',
//		];
//
//		$a = \App\Order\Modules\Service\OrderWithhold::repaymentNotify($data);
//		v($a);
//		die;
//		$a = new OrderNotice(1, 1, SceneConfig::ORDER_CREATE);
//		$a->notify();
	}
}
?>
