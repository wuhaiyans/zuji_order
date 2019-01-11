<?php
namespace App\Order\Controllers\Api\v1;

use App\Order\Modules\Repository\Pay\UnderPay\OrderBuyout;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Inc\OrderInstalmentStatus;

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

		$a = 18.33;
		$b = 18.32;

		$amount = bcsub($a, $b, 2);
		$amount = max($amount , 0.01);
		p($amount);
		v($a,1);
		v($b,1);

		v(bcsub($b,$a,2));
		$amount = bcmul($a,100) - bcmul($b,100);
//			$amount = $amount > 0 ? $amount : 0.01;
		p($amount);

		die;
		$_data = [
			'zujin'		    => '0.5',	//【必选】price 每期租金
			'zuqi'		    => 12,	    //【必选】int 租期（必选保证大于0）
			'insurance'    	=> '0.01',	//【必选】price 保险金额
		];

		$sku = [

				[
					'discount_amount' => '0.01',
					'zuqi_policy' => 'avg',// 分期类型根据优惠券类型来进行分期 serialize 分期顺序优惠 （递减）
				],
//				[
//					'discount_amount' => '0.1',
//					'zuqi_policy' => 'first',// 分期类型根据优惠券类型来进行分期 serialize 分期顺序优惠 （递减）
//				]
		];


		$computer = new \App\Order\Modules\Repository\Instalment\MonthComputer( $_data );

		// 优惠策略
		foreach( $sku as $dis_info ){
			// 分期策略：平均优惠
			if( $dis_info['zuqi_policy'] == 'avg' ){
				$discounter_simple = new \App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter( $dis_info['discount_amount'] );
				$computer->addDiscounter( $discounter_simple );
			}
//			else if($dis_info['zuqi_policy'] == 'first'){
//				$discounter_first = new \App\Order\Modules\Repository\Instalment\Discounter\FirstDiscounter( $dis_info['discount_amount'] );
//				$computer->addDiscounter( $discounter_first );
//			}


		}
		$a =  $computer->compute();
		p($a);
	}
}
?>
