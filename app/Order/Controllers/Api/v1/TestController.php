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

		$data = [

			'name'			=> "订金索赔扣押金",
			'out_trade_no' 	=> '20A80191759992478', //业务系统授权码
			'fundauth_no' 	=> '20A80191759992478', //支付系统授权码
			'amount' 		=> bcmul(1,100), //交易金额；单位：分
			'back_url' 		=> config('app.url') . "/order/pay/withholdCreatePayNotify",
			'user_id' 		=> 3209, //用户id

		];

		$succss = \App\Lib\Payment\CommonFundAuthApi::unfreezeAndPay($data);

		v($succss);

//
//		// 发送短信
//		$notice = new \App\Order\Modules\Service\OrderNotice(
//			\App\Order\Modules\Inc\OrderStatus::BUSINESS_RELET,
//			"XAB3048073127160",
//			"ReletSuccess");
//		$notice->notify();



	}
}
?>
