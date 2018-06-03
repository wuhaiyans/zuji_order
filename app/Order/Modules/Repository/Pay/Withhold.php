<?php


namespace App\Order\Modules\Repository\Pay;

use App\Lib\Common\LogApi;
use App\Lib\Common\Error;

/**
 * 代扣
 *
 * @author Administrator
 */
class Withhold extends \App\Lib\Configurable {
	
	protected $user_id = 0;
	
	protected $withhold_no = '';
	
	protected $out_withhold_no = '';
	
	protected $withhold_status = '';
	
	protected $withhold_channel = 0;
	
	protected $sign_time = 0;
	
	protected $unsign_time = 0;
	
	protected $counter = 0;
	
	protected $update_time = 0;


	/**
	 * 构造函数
	 * @param array $data
	 * [
	 *		'user_id'			=> '',		// 用户ID
	 *		'withhold_no'		=> '',		// 业务系统代扣协议
	 *		'out_withhold_no'	=> '',		// 支付系统代扣协议
	 *		'counter'			=> '',		// 业务计数
	 * ]
	 */
	public function __construct(array $data=[]) {
		parent::__construct($data);
	}
	
	protected function init() {
		if( $this->user_id<1 ){
			throw new \Exception('[user_id]初始化失败');
		}
		if( empty($this->withhold_no) ){
			throw new \Exception('[withhold_no]初始化失败');
		}
		if( empty($this->out_withhold_no) ){
			throw new \Exception('[out_withhold_no]初始化失败');
		}
		if( !is_numeric($this->counter) ){
			throw new \Exception('[counter]初始化失败');
		}
		parent::init();
	}

	/**
	 * 是否有效
	 * @return bool
	 */
	public function isValid():bool{
		return $this->withhold_status == WithholdStatus::SIGNED;
	}

	/**
	 * 使用
	 * 增加代扣协议业务数
	 * 当前协议必须是已签约状态，是有效的
	 * @return bool true：成功；false：失败
	 */
	public function increase(){
		if( $this->withhold_status != WithholdStatus::SIGNED ){
			return false;
		}
		$n = \App\Order\Models\OrderPayWithholdModel::where([
			'withhold_no'	=> $this->withhold_no,
		])->limit(1)->increment('counter');
		if( $n ){
			++$this->counter;
			return true;
		};
		return false;
	}
	
	/**
	 * 释放
	 * 减少代扣协议业务数
	 * 当前协议必须是已签约状态，是有效的
	 * @return bool true：成功；false：失败
	 */
	public function decrease(){
		if( $this->withhold_status != WithholdStatus::SIGNED ){
			return false;
		}
		if( $this->counter==0 ){
			return false;
		}
		$n = \App\Order\Models\OrderPayWithholdModel::where([
			'withhold_no'	=> $this->withhold_no,
		])->limit(1)->decrement('counter');
		if( $n ){
			--$this->counter;
			return true;
		};
		return false;
	}
	
	/**
	 * 读取代扣协议的业务数
	 * @return int 
	 */
	public function getCounter(){
		return $this->counter;
	}
	
	/**
	 * 解约申请
	 * @return bool true:申请成功；false：申请失败
	 * @throws \Exception
	 */
	public function unsignApply(){
		if( !$this->_unsignBefore() ){
			return false;
		}
		$time = time();
		try {
//			var_dump( config('app.url').'/order/pay/withholdUnsignNotify' );
			// 申请解约
			\App\Lib\Payment\CommonWithholdingApi::unSign([
				'agreement_no'		=> $this->out_withhold_no,	// 支付系统代扣协议编号
				'out_agreement_no'	=> $this->withhold_no,		// 业务系统代扣协议编号
				'user_id'			=> $this->user_id,
				'back_url'			=> config('app.url').'/order/pay/withholdUnsignNotify',
			]);

			$n = \App\Order\Models\OrderPayWithholdModel::where([
				'withhold_no'	=> $this->withhold_no,
			])->limit(1)->update([
				'withhold_status' => WithholdStatus::UNSIGNING,
				'update_time' => $time,
			]);
			if( !$n ){
				Error::setError('[代扣协议][解约中]状态更新失败');
				LogApi::type('data-save')::error('[代扣协议][解约中]状态更新失败');
				return false;
			};
			$this->withhold_status = WithholdStatus::UNSIGNING;
			$this->update_time = $time;
			return true;
		} catch (\Exception $exc) {
			Error::exception( $exc );
			return false;
		}

	}
	/**
	 * 解约成功
	 */
	public function unsignSuccess(){
		if( !$this->_unsignBefore() ){
			return false;
		}
		$time = time();
		// 修改状态
		sql_profiler();
		$n = \App\Order\Models\OrderPayWithholdModel::where([
			'withhold_no'	=> $this->withhold_no,
		])->limit(1)->update([
			'withhold_status' => WithholdStatus::UNSIGNED,
			'unsign_time' => $time,
			'update_time' => $time,
		]);
		if( !$n ){
			Error::setError('[代扣协议][已解约]状态更新失败');
			LogApi::type('data-save')::error('[代扣协议][已解约]状态更新失败');
			return false;
		};
		$this->withhold_status = WithholdStatus::UNSIGNED;
		$this->unsign_time = $time;
		$this->update_time = $time;
		return true;
	}
	
	private function _unsignBefore(){
		// 协议状态判断
		if( !$this->isValid() ){
			Error::setError('代扣协议已失效');
			return false;
		}
		// 无在用业务
		if( $this->counter > 0 ){
			Error::setError('代扣协议正在使用');
			return false;
		}
		return true;
	}
	
}
