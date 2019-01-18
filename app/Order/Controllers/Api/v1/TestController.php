<?php
namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Repository\Pay\UnderPay\OrderBuyout;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Inc\OrderInstalmentStatus;
use Illuminate\Http\Request;

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

	public function sendSms(Request $request) {


//		$notice = new \App\Order\Modules\Service\OrderNotice(
//			\App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI,
//			'YQB11521593324985',
//			"OverdueDeduction");
//		$notice->notify();
//
//
//		die;
//
//		$a = 18.33;
//		$b = 18.32;
//
//		$amount = bcsub($a, $b, 2);
//		$amount = max($amount , 0.01);
//		p($amount);
//		v($a,1);
//		v($b,1);
//
//		v(bcsub($b,$a,2));
//		$amount = bcmul($a,100) - bcmul($b,100);
////			$amount = $amount > 0 ? $amount : 0.01;
//		p($amount);
//
//		die;

		$allParams = $request->all();
		$params =   $allParams['params'];

		$zujin 				= $params['zujin'];
		$zuqi 				= $params['zuqi'];
		$insurance 			= $params['insurance'];
		$discount_amount 	= $params['discount_amount'];
		$type 				= $params['type'];
		$day				= $params['day'];
		$_data = [
			'zujin'		    => $zujin,		//【必选】price 每期租金
			'zuqi'		    => $zuqi,	    //【必选】int 租期（必选保证大于0）
			'insurance'    	=> $insurance,	//【必选】price 保险金额
		];

		$type = $type == 1 ? "avg" : "first";
		$sku  = [
				[
					'discount_amount' => $discount_amount,
					'zuqi_policy' => $type,// 分期类型根据优惠券类型来进行分期 serialize 分期顺序优惠 （递减）
				]
		];
//		p($sku);
		if($day == 1){
			$computer = new \App\Order\Modules\Repository\Instalment\MonthComputer( $_data );
		}else{
			$computer = new \App\Order\Modules\Repository\Instalment\DayComputer( $_data );
		}

		// 优惠策略
		foreach( $sku as $dis_info ){
			// 分期策略：平均优惠
			if( $dis_info['zuqi_policy'] == 'avg' ){
				$discounter_simple = new \App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter( $dis_info['discount_amount'] );
				$computer->addDiscounter( $discounter_simple );
			}
			else if($dis_info['zuqi_policy'] == 'first'){
				$discounter_first = new \App\Order\Modules\Repository\Instalment\Discounter\FirstDiscounter( $dis_info['discount_amount'] );
				$computer->addDiscounter( $discounter_first );
			}


		}
		$a =  $computer->compute();
		p($a);
	}
}
?>
