<?php
namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Service\OrderNotice;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;

class TestController extends Controller
{
	public function sendSms() {

		$a = new \App\Order\Controllers\Api\v1\WithholdController();
		$b = $a->crontab_createpay();
		v($b);

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
