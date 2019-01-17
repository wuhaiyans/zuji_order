<?php
namespace App\Order\Modules\Repository\Instalment\Discounter;

/**
 * 普通优惠
 * 优惠金额平均计算到每一个账期（余数加入到首页）
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class SimpleDiscounter implements Discounter {

	/**
	 * 优惠金额，单位：元
	 * @var float
	 */
	protected $discount_amount;

	/**
	 *
	 * @param float $discount_amount
	 */
	public function __construct( $discount_amount ){
		$this->discount_amount = $discount_amount;

	}

	/**
	 * 优惠计算
	 * @param array $params		分期列表参数（二维数组）
	 * [
	 *		[
	 *			'term'			=> '',//【必选】int 期(yyyymm)
	 *			'times'			=> '',//【必选】int 第几期
	 *			'day'			=> '',//【必选】int 扣款日
	 *			'original_amount'	=> '',//【必选】price 每期金额
	 *			'discount_amount'	=> '',//【必选】price 每期优惠金额
	 *			'amount'			=> '',//【必选】price 每期应还金额
	 *		]
	 * ]
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function discount( array $params ){
		if($this->discount_amount == 0){
			return $params;
		}

		$totalAmount = 0;

		foreach($params as $v){
			$totalAmount += $v['amount'];
		}

		$totalAmount -= $this->discount_amount;
		$totalAmount = max($totalAmount , 0);

		// 分期数
		$n = count( $params );
		// 余数
		$remainderAmount = bcmod($totalAmount * 100,$n);
		$remainderAmount = $remainderAmount / 100;

		// 平均优惠

		$avgDiscount = bcsub($totalAmount, $remainderAmount, 2)/$n;
		
		foreach( $params as &$item ){
			// 应付金额
			if($avgDiscount > 0){
				$item['discount_amount'] = bcsub($item['amount'], $avgDiscount, 2);
			}else{
				$item['discount_amount'] = $item['amount'];
			}

			$item['amount'] = $avgDiscount;
		}

		$params[0]['amount'] += $remainderAmount;
		// 两位小数精度
		$params[0]['discount_amount'] = bcsub($params[0]['original_amount'], $params[0]['amount'],2);
		$params[0]['discount_amount'] = max($params[0]['discount_amount'],0);
		return $params;
//
//		// 分期数
//		$n = count( $params );
//		// 余数
//		$remainder_discount = ($this->discount_amount * 100) % ($n);
//		$remainder_discount = $remainder_discount / 100;
//		// 平均优惠
//		$avg_discount = ($this->discount_amount-$remainder_discount)/$n;
//
//		foreach( $params as &$item ){
//			// 计算实际可优惠的金额
//			$temp = 0;
//			// 全优惠
//			if( $item['amount'] >= $avg_discount ){
//				$temp = $avg_discount;
//			}
//			// 优惠至0元
//			else{
//				$temp = $item['amount'];
//			}
//			// 优惠金额
//			$item['discount_amount'] += $temp;
//			// 应付金额
//			$item['amount'] -= $temp;
//		}
//		// 优惠余数，加到首期
//		$temp = 0;
//		// 全优惠
//		if( $params[0]['amount'] > $remainder_discount ){
//			$temp = $remainder_discount;
//		}
//		// 优惠至0元
//		else{
//			$temp = $params[0]['amount'];
//		}
//		$params[0]['discount_amount'] += $temp;
//		$params[0]['amount'] -= $temp;
//
//		return $params;
	}
}
