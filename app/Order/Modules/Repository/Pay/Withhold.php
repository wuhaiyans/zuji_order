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
	 * 绑定业务
	 * 增加代扣协议业务数
	 * 当前协议必须是已签约状态，是有效的
	 * @param array $params 业务标记
	 * [
	 *		'business_type' => '',	// 【必须】int		业务类型
	 *		'business_no'	=> '',	// 【必须】string	业务编码
	 * ]
	 * @return bool true：成功；false：失败
	 */
	public function bind( array $params ){
		if( $this->withhold_status != WithholdStatus::SIGNED ){
			Error::setError('[代扣协议]状态禁止');
			return false;
		}

		$bwModel = new \App\Order\Models\OrderPayWithholdBusinessModel();

		// 查找 业务是否绑定了协议
		$wb_info = $bwModel->where([
			'business_type'	=> $params['business_type'],
			'business_no'	=> $params['business_no'],
			'unbind_time'	=> 0,// 未解绑的协议
		])->first();
		// 存在，禁止重复创建
		if( $wb_info ){
			Error::setError('[代扣协议][业务关系]绑定重复操作');
			LogApi::debug('[代扣协议][业务关系]绑定重复操作');
			return false;
		}

		// 创建 业务绑定协议
		$id = $bwModel->insert([
			'withhold_no'	=> $this->withhold_no,
			'business_type'	=> $params['business_type'],
			'business_no'	=> $params['business_no'],
			'bind_time'		=> time(),// 绑定时间
			'unbind_time'	=> 0,// 
		]);
		if( !$id ){
			Error::setError('[代扣协议][业务关系]绑定失败');
			LogApi::type('data-save')::debug('[代扣协议][业务关系]绑定失败');
			return false;
		}

		// 增加业务计数
		$n = \App\Order\Models\OrderPayWithholdModel::where([
			'withhold_no'	=> $this->withhold_no,
		])->limit(1)->increment('counter');
		if( $n ){
			++$this->counter;
			return true;
		}

		Error::setError('业务计数失败');
		return false;
	}

	/**
	 * 解绑业务
	 * 减少代扣协议业务数
	 * 当前协议必须是已签约状态，是有效的
	 * @param array $params 业务标记
	 * [
	 *		'business_type' => '',	// 【必须】int		业务类型
	 *		'business_no'	=> '',	// 【必须】string	业务编码
	 * ]
	 * @return bool true：成功；false：失败
	 */
	public function unbind( array $params ){
		if( $this->withhold_status != WithholdStatus::SIGNED ){
			Error::setError('[代扣协议]状态禁止');
			return false;
		}
		if( $this->counter==0 ){
			Error::setError('[代扣协议]业务计数超限');
			return false;
		}

		$time = time();

		$bwModel = new \App\Order\Models\OrderPayWithholdBusinessModel();

		// 查找 业务是否绑定了协议
		$wb_info = $bwModel->where([
			'business_type'	=> $params['business_type'],
			'business_no'	=> $params['business_no'],
			'unbind_time'	=> 0,// 未解绑的协议
		])->first();
		// 不存在
		if( !$wb_info ){
			Error::setError('[代扣协议][业务关系]不存在');
			LogApi::debug('[代扣协议][业务关系]不存在');
			return false;
		}

		// 解绑
		$n = $bwModel->limit(1)
			->where(['id' =>$wb_info['id']])
			->update([
				'unbind_time' => $time,
			]);

		if( !$n ){
			Error::setError('[代扣协议][业务关系]解绑失败');
			LogApi::type('data-save')::error('[代扣协议][业务关系]解绑失败');
			return false;
		}
		// 修改 业务计数
		$n = \App\Order\Models\OrderPayWithholdModel::where([
			'withhold_no'	=> $this->withhold_no,
		])->limit(1)->decrement('counter');
		if( $n ){
			--$this->counter;
			return true;
		};
		Error::setError('业务计数失败');
		LogApi::type('data-save')::debug('[代扣协议][业务关系]解绑失败');
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
		
		// 协议状态判断
		if( $this->withhold_status == WithholdStatus::UNSIGNED ){	// 已解约，重复请求
			LogApi::debug('代扣解约申请重复，忽略', $this->getData());
			return true;
		}
		elseif( $this->withhold_status != WithholdStatus::SIGNED ){	// 已签约
			LogApi::debug('代扣解约状态禁止', $this->getData());
			Error::setError('代扣协议状态错误');
			return false;
		}
		// 无在用业务
		if( $this->counter > 0 ){
			Error::setError('代扣协议正在使用');
			return false;
		}
		
		$time = time();
		try {

			// 申请解约
			\App\Lib\Payment\CommonWithholdingApi::unSign([
				'agreement_no'		=> $this->out_withhold_no,	// 支付系统代扣协议编号
				'out_agreement_no'	=> $this->withhold_no,		// 业务系统代扣协议编号
				'user_id'			=> $this->user_id,
				'back_url'			=> config('app.url').'/order/pay/withholdUnsignNotify',
			]);

			// 更新协议状态
			$n = \App\Order\Models\OrderPayWithholdModel::where([
				'withhold_no'	=> $this->withhold_no,
			])->limit(1)->update([
				'withhold_status' => WithholdStatus::UNSIGNING,
				'update_time' => $time,
			]);
			if( !$n ){
				LogApi::type('data-save')::error('[代扣协议][解约申请]状态更新失败');
				Error::setError('[代扣协议][解约申请]状态更新失败');
				return false;
			};
			$this->withhold_status = WithholdStatus::UNSIGNING;
			$this->update_time = $time;
			return true;
		} catch (\App\Lib\ApiException $exc) {
				LogApi::type('api-error')::error('[代扣协议][解约申请]失败',$exc);
				Error::exception( $exc );
			return false;
		}

	}
	/**
	 * 解约成功
	 */
	public function unsignSuccess(){
		// 协议状态判断
		if( $this->withhold_status == WithholdStatus::UNSIGNED ){	// 已解约，重复请求
			LogApi::debug('代扣解约重复通知，忽略', $this->getData());
			return true;
		}
		// 协议状态判断
		elseif( $this->withhold_status != WithholdStatus::SIGNED	// 已签约
				&& $this->withhold_status != WithholdStatus::UNSIGNING ){	// 解约中
			LogApi::debug('代扣解约状态错误', $this->getData());
			Error::setError('代扣协议状态错误');
			return false;
		}
		// 无在用业务
		if( $this->counter > 0 ){
			Error::setError('代扣协议正在使用');
			return false;
		}
		
		$time = time();
		// 修改状态
		//sql_profiler();
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

	public function getData(){
		return [
			'withhold_no'		=> $this->withhold_no,
			'out_withhold_no'	=> $this->out_withhold_no,
			'withhold_status'	=> $this->withhold_status,
		];
	}

}
