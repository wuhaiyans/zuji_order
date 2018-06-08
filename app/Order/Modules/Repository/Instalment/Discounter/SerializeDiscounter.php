<?php
namespace App\Order\Modules\Repository\Instalment\Discounter;

/**
 * 按账期顺序优惠
 * 先优惠第一期，然后计算剩余优惠金额，再优惠第二期，依次类推，知道剩余优惠金额为0
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class SerializeDiscounter implements Discounter {
	
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
		return $this;
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
		
		foreach( $params as &$item ){
			// 剩余优惠金额为0时，停止优惠计算
			if( $this->discount_amount == 0 ){
				break;
			}
			// 应还金额为0时，进入下一个账单
			if( $item['amount'] == 0 ){
				continue;
			}
			// 剩余优惠金额 > 应还金额  =>  剩余金额 - 应还金额
			if( $this->discount_amount >= $item['amount'] ){
				// 计算剩余优惠金额
				$this->discount_amount -= $item['amount'];
				// 计算优惠金额
				$item['discount_amount'] += $item['amount'];
				// 应还金额设置为0
				$item['amount'] = 0;
			}else{
				// 计算应还金额
				$item['amount'] -= $this->discount_amount;
				// 计算优惠金额
				$item['discount_amount'] += $this->discount_amount;
				// 剩余优惠金额设置为0
				$this->discount_amount = 0;
			}
		}
		return $params;
	}
}
