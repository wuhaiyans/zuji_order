<?php
namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Repository\Pay\UnderPay\OrderBuyout;
use App\Order\Modules\Service\OrderNotice;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;

class TestController extends Controller
{
	public function test(){
		$param = [
			'order_no'=>'A820162514895949',
			'amount'=>1000
		];
		$orderBuyout = new OrderBuyout($param);
		$amount = $orderBuyout->getPayAmount();
		$orderBuyout->execute();
		echo $amount;
	}

	public function sendSms() {
		$a = \App\Order\Modules\Service\OrderWithhold::instalment_withhold(768186);
		v($a);



	}
}
?>
