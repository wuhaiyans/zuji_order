<?php
namespace App\Order\Modules\Repository\Instalment;

use App\Order\Modules\Repository\Instalment\Discounter\Discounter;

/**
 * 分期计算
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
abstract class Computer {
	
	protected $year = 0;
	protected $month = 0;
	protected $day = 0;
	
	protected $times = 1;
	
	/**
	 * 待计算租赁信息
	 * @var array
	 * [
	 *		'zujin'		=> '',	//【必选】price 每期租金
	 *		'zuqi'		=> '',	//【必选】price 租期（必选保证大于0）
	 *		'insurance' => '',	//【必选】price 保险金额
	 * ]
	 */
	protected $params;
	
	/**
	 * 优惠器列表
	 * @var array
	 */
	protected $discounters = [];
	
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
		$begin_time = time();//默认时间戳
		$this->params = $params;
		if( isset($params['end_time']) && $params['end_time']>0 ){
			$begin_time = $params['end_time'];
		}
		$this->setBeginTime( $begin_time );
	}
	
	/**
	 * 添加优惠计算器
	 * @param \App\Order\Modules\Repository\Instalment\DiscountInterface $discounter
	 * @return $this
	 */
	public function addDiscounter(Discounter $discounter ){
		$this->discounters[] = $discounter;
		return $this;
	}
		
	/**
	 * 设置开始时间
	 */
	public function setBeginTime( int $timestamp ){
		list($this->year,$this->month,$this->day) = explode('-',date('Y-m-d',$timestamp));
		$this->day = intval( $this->day );
	}
	
	/**
	 * 设置开始期数
	 */
	public function setTimes( int $times=1 ){
		$this->times = $times;
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
	abstract public function compute( ):array;
	
	
	protected function _theTerm( ){
		return sprintf('%d%02d',$this->year,$this->month);
	}
	protected function _theDay( ){
		return $this->day;
	}
	protected function _theTimes( ){
		return $this->times++;
	}
	
	protected function _nextTimestamp( ){
		$hour = 0;
		$minute = 0;
		$second = 0;
		$month = $this->month+1;
		$day = $this->day;
		$year = $this->year;
		$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
		
		list($this->year,$this->month,$this->day) = explode('-',date('Y-m-d',$timestamp));
		$this->day = intval( $this->day );
	}
}
