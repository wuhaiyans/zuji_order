<?php
namespace App\Order\Modules\Repository\Instalment;

use App\Order\Modules\Repository\Instalment\Discounter\Discounter;

/**
 * 分期计算（长租，一月一期）
 * 
 * 输入：
 *		每期租金
 *		租期
 *		保险金额
 *		商品租金优惠金额
 *		优惠计算器
 * 输出：分期列表，每期信息如下：
 *		原分期金额
 *		优惠金额额
 *		应还金额
 *		
 * @author
 */
class MonthComputer extends Computer {
	
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
		// 默认取下一个月日期
		$this->_nextTimestamp();
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
		$result = [];
		
		for( $i=0; $i< $this->params['zuqi']; ++$i ){
			$result[] = [
				'term'		=> $this->_theTerm(),
				'day'		=> $this->_theDay(),
				'times'		=> $this->_theTimes(),
				'original_amount'			=> $this->params['zujin'],
				'discount_amount'	=> 0,
				'amount'					=> $this->params['zujin'],
			];
			// 计算下一个时间点
			$this->_nextTimestamp();
		}
		
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
