<?php
namespace App\Order\Modules\Repository\Instalment\Discounter;

/**
 * 首月优惠
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class FirstDiscounter implements Discounter {
	
	/**
	 * 优惠金额，单位：元
	 * @var float
	 */
	private $discount_amount = 0;
	
	public function __construct( $discount_amount ) {
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
		// 优惠首期
		$temp_discount = 0;
		if( $params[0]['amount'] > $this->discount_amount ){
			$params[0]['amount'] -= $this->discount_amount;
			$temp_discount = $this->discount_amount;
		}else{
			$temp_discount = $params[0]['amount'];
			$params[0]['amount'] = 0;

		}
		// 重新计算优惠
		$params[0]['discount_amount'] += $temp_discount;

		return $params;
	}
}
