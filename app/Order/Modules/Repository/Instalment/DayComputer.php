<?php
namespace App\Order\Modules\Repository\Instalment;

use App\Order\Modules\Repository\Instalment\Discounter\SimpleDiscounter;
use App\Order\Modules\Repository\Instalment\Discounter\Discounter;

/**
 * 分期计算（日租，n天作为一期）
 * 
 */
class DayComputer extends Computer {
	
	
	/**
	 * 
	 * @param array $params
	 * [
	 *		'zujin'		=> '',	//【必选】price 每期租金
	 *		'zuqi'		=> '',	//【必选】int 租期（必选保证大于0）
	 *		'insurance' => '',	//【必选】price 保险金额
	 *		'begin_time'	=> '',	//【可选】int 开始日期时间戳
	 * ]
	 */
	public function __construct( $params ) {
		parent::__construct( $params );
	}
	
	
	/**
	 * 计算
	 * @return array 分期列表
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
	 */
	public function compute( ):array{
		// 短暂，只生成一期
		$amount = $this->params['zujin']*$this->params['zuqi'];
		$result[] = [
			'term'		=> $this->_theTerm(),
			'times'		=> $this->_theTimes(),
			'day'		=> $this->_theDay(),
			'original_amount'			=> $amount,
			'amount'					=> $amount,
			'discount_amount'	=> 0,
		];
		
		// 优惠器计算优惠
		foreach($this->discounters as $item ){
			$result = $item->discount( $result );
		}
		
		// 首期 保险金额
		$result[0]['original_amount'] += $this->params['insurance'];
		$result[0]['amount'] += $this->params['insurance'];
		
		return $result;
		
	}
	
}
