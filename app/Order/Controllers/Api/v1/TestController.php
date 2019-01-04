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
		$youhui = 0;
		// 租金抵用券
		$couponInfo = \App\Lib\Coupon\Coupon::getUserCoupon(3209,1);
		p($couponInfo);

		// 创建优惠券使用记录
		$couponData = [
			'coupon_id'         => 19,
			'discount_amount'   => '0.01',
			'business_type'     => \App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI,
			'business_no'       => 'FB10489812984066',
		];
		$couponId = \App\Order\Modules\Repository\OrderCouponRepository::add($couponData);
		if(!$couponId){
			return apiResponse([], ApiStatus::CODE_71001, "创建优惠券使用记录失败");
		}



		die;

		$_data = [
			'zujin'		    => '159.00',	//【必选】price 每期租金
			'zuqi'		    => 12,	    //【必选】int 租期（必选保证大于0）
			'insurance'    	=> '199.00',	//【必选】price 保险金额
		];

		$sku = [
				[
					'discount_amount' => '1717.20',
					'zuqi_policy' => 'avg',// 分期类型根据优惠券类型来进行分期 serialize 分期顺序优惠 （递减）
				]
		];


		$computer = new \App\Order\Modules\Repository\Instalment\MonthComputer( $_data );

		// 优惠策略
		foreach( $sku as $dis_info ){
			// 分期策略：平均优惠
			if( $dis_info['zuqi_policy'] == 'avg' ){
				$discounter_simple = new \App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter( $dis_info['discount_amount'] );
				$computer->addDiscounter( $discounter_simple );
			}

		}
		$a =  $computer->compute();
		p($a);
	}
}
?>
