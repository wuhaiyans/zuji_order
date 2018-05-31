<?php
namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Service\OrderNotice;
use App\Order\Modules\Repository\ShortMessage\SceneConfig;

class TestController extends Controller
{
	public function sendSms() {
		$a = new OrderNotice(1, 1, SceneConfig::ORDER_CREATE);
		$a->notify();
	}
}
?>
