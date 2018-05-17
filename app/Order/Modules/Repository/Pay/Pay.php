<?php
/**
 * App\Order\Modules\Repository\Pay\Pay.php
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace App\Order\Modules\Repository\Pay;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderPayModel;
use App\Order\Models\OrderPayPaymentModel;
use App\Order\Models\OrderPayWithholdModel;
use App\Order\Models\OrderPayFundauthModel;

/**
 * 支付 类
 * <p>标准化支付整体流程，定义支付环节和接口</p>
 * <p>支付阶段分为3个环节，执行顺序依次是：</p>
 * <ul>
 * <li>支付</li>
 * <li>代扣签约</li>
 * <li>资金预授权</li>
 * </ul>
 * <p>注：每个环节都是可选的，但至少存在一个环节（支付阶段才有意义）</p>
 * @method bool paymentSuccess(array $params) 用于支付完成时调用，进入下一个状态
 * @method bool withholdSuccess(array $params) 用于代扣签约完成时调用，进入下一个状态
 * @method bool fundauthSuccess(array $params) 用于资金预授权完成时调用，进入下一个状态
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Pay extends \App\Lib\Configurable 
{
	
	//-+------------------------------------------------------------------------
	// | 
	//-+------------------------------------------------------------------------
	/**
	 * 业务类型
	 * @var string
	 */
	protected $businessType = 0;
	
	/**
	 * 业务编号
	 * @var string
	 */
	protected $businessNo = '';
	
	/**
	 * 状态
	 * @var int
	 */
	protected $status = '';
	
	/**
	 * 创建时间戳
	 * @var int
	 */
	protected $createTime = 0;
	
	/**
	 * 更新时间戳
	 * @var int
	 */
	protected $updateTime = 0;
	
	//-+------------------------------------------------------------------------
	// | 支付相关
	//-+------------------------------------------------------------------------
	protected $paymentStatus = 0;
	protected $paymentChannel = 0;
	protected $paymentAmount = 0;
	protected $paymentFenqi = 0;
	protected $paymentNo = '';
	
	//-+------------------------------------------------------------------------
	// | 代扣签约相关
	//-+------------------------------------------------------------------------
	protected $withholdStatus = 0;
	protected $withholdChannel = 0;
	protected $withholdNo = '';
	
	//-+------------------------------------------------------------------------
	// | 资金预授权相关
	//-+------------------------------------------------------------------------
	protected $fundauthStatus = 0;
	protected $fundauthChannel = 0;
	protected $fundauthAmount = 0.00;
	protected $fundauthNo = '';
	
	public function __construct(array $data=[]) {
		parent::__construct($data);
	}
	
	/**
	 * 当前状态
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}
	
	/**
	 * 当前payment状态
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return int
	 */
	public function getPaymentStatus()
	{
		return $this->paymentStatus;
	}
	
	/**
	 * 当前payment状态
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return int
	 */
	public function getWithholdStatus()
	{
		return $this->withholdStatus;
	}

	/**
	 * 当前payment状态
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return int
	 */
	public function getFundauthStatus()
	{
		return $this->fundauthStatus;
	}
	
	/**
	 * 是否支付成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function isSuccess(){
		return $this->status == PayStatus::SUCCESS;
	}
	
	/**
	 * 是否支付成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function paymentIsSuccess(){
		return $this->paymentStatus == PaymentStatus::PAYMENT_SUCCESS;
	}
	
	/**
	 * 是否签约代扣成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function withholdIsSuccess(){
		return $this->withholdStatus == WithholdStatus::SIGNED;
	}
	
	/**
	 * 是否资金预授权成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function fundauthIsSuccess(){
		return $this->fundauthStatus == FundauthStatus::SUCCESS;
	}
	
	/**
	 * 取消
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 * @throws \Exception
	 */
	public function cancel(){
		LogApi::debug('[支付阶段]取消');
		if( $this->status == PayStatus::CLOSED ){
			LogApi::debug('[支付阶段]取消操作重复');
			throw new \Exception('支付取消失败：重复操作');
		}
		LogApi::debug('[支付阶段]取消成功');
		$this->status = PayStatus::CLOSED;
		return true;
	}
	
	/**
	 * 支付成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array		$params		支付成功参数
	 * [
	 *		'out_payment_no'	=> '',	// 支付系统支付码
	 *		'payment_time'	=> '',	// 支付时间
	 * ]
	 * @return bool
	 * @throws \Exception
	 */
	public function paymentSuccess( array $params )
	{
		LogApi::debug('[支付阶段]支付环节支付处理');
		// 只有待支付状态时才允许操作
		if( $this->status != PayStatus::WAIT_PAYMENT 
				|| $this->paymentStatus != PaymentStatus::WAIT_PAYMENT ){
			LogApi::error('[支付阶段]状态错误');
			throw new \Exception('支付环节支付失败：状态错误');
		}
		
		// 下一个状态
		$status = $this->_getNextStatus();
		
		// 更新 支付阶段 表
		$payModel = new OrderPayModel();
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'status' => $status,
			'payment_status' => PaymentStatus::PAYMENT_SUCCESS,
		]);
		if( !$b ){
			LogApi::error('[支付阶段]支付环节支付保存失败');
			throw new \Exception( '支付完成保存失败' );
		}
		// 更新 支付环节 表
		$paymentModel = new OrderPayPaymentModel();
		$b = $paymentModel->insert([
			'payment_no' => $this->paymentNo,
			'out_payment_no' => $params['out_payment_no'],
			'create_time' => time(),
		]);
		if( !$b ){
			LogApi::error('[支付阶段]支付环节支付保存失败');
			throw new \Exception( '支付失败' );
		}
		
		$this->status = $status;
		$this->paymentStatus = PaymentStatus::PAYMENT_SUCCESS;
		
		LogApi::debug('[支付阶段]支付环节支付处理成功');
		// 状态回调
		$this->_statusCallback( 'payment' );
		return true;
	}
	
	/**
	 * 代扣签约成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array		$params		签约成功参数
	 * [
	 *		'out_withhold_no'	=> '',	// 支付系统代扣码
	 *		'uid'				=> '',	// 用户ID
	 * ]
	 * @return bool
	 * @throws \Exception
	 */
	public function withholdSuccess( array $params )
	{
		LogApi::debug('[支付阶段]代扣签约环节处理');
		// 待签约时才允许
		if( $this->status != PayStatus::WAIT_WHITHHOLD
				|| $this->withholdStatus != WithholdStatus::WAIT_WITHHOLD )
		{
			LogApi::error('[支付阶段]代扣签约环节状态错误');
			throw new \Exception('代扣签约环节签约失败：状态错误');
		}
		
		// 下一个状态
		$status = $this->_getNextStatus();
		
		// 更新 支付阶段 表
		$payModel = new OrderPayModel();
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'status' => $status,
			'withhold_status' => WithholdStatus::SIGNED,// 已签约
		]);
		if( !$b ){
			LogApi::error('[支付阶段]代扣签约环节处理保存失败1');
			throw new \Exception( '代扣签约环节完成保存失败' );
		}
		// 更新 代扣签约环节 表
		$withholdModel = new OrderPayWithholdModel();
		$b = $withholdModel->insert([
			'withhold_no' => $this->withholdNo,
			'out_withhold_no' => $params['out_withhold_no'],
			'uid' => $params['uid'],
			'withhold_status' => WithholdStatus::SIGNED,// 已签约
			'sign_time' => time(),
			'counter' => 1, // 计数
		]);
		if( !$b ){
			LogApi::error('[支付阶段]代扣签约环节处理保存失败2');
			throw new \Exception( '代扣签约环节完成保存失败' );
		}
		
		$this->status = $status;
		$this->withholdStatus = WithholdStatus::SIGNED;// 已签约
		
		LogApi::debug('[支付阶段]代扣签约环节处理完成');
		
		// 状态回调
		$this->_statusCallback( 'withhold' );
		
		return true;
	}
	
	/**
	 * 资金预授权成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array		$params		支付成功参数
	 * [
	 *		'out_fundauth_no'	=> '',	// 支付系统授权码
	 *		'uid'				=> '',	// 用户ID
	 *		'total_amount'		=> '',	// 预授权金额；单位：元
	 * ]
	 * @return bool
	 * @throws \Exception
	 */
	public function fundauthSuccess( array $params )
	{
		if( $this->status != PayStatus::WAIT_FUNDAUTH 
				|| $this->fundauthStatus != FundauthStatus::WAIT_FUNDAUTH ){
			throw new \Exception('资金预授权环节状态错误');
		}
		
		// 下一个状态
		$status = $this->_getNextStatus();
		
		// 更新 支付阶段 表
		$payModel = new OrderPayModel( );
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'status' => $status,
			'fundauth_status' => FundauthStatus::SUCCESS,// 已授权
		]);
		if( !$b ){
			throw new \Exception( '预授权环节完成保存失败' );
		}
		// 更新 预授权环节 表
		$fundauthModel = new OrderPayFundauthModel();
		$b = $fundauthModel->insert([
			'fundauth_no' => $this->fundauthNo,
			'out_fundauth_no' => $params['out_fundauth_no'],
			'uid' => $params['uid'],
			'fundauth_status' => FundauthStatus::SUCCESS,// 已授权
			'freeze_time' => time(),
			'total_amount' => $this->fundauthAmount,
		]);
		if( !$b ){
			throw new \Exception( '预授权环节完成保存失败' );
		}
		
		$this->status = $status;
		$this->fundauthStatus = FundauthStatus::SUCCESS;
		// 状态回调
		$this->_statusCallback( 'fundauth' );
		return true;
	}
	
	
	/**
	 * 是否需要 payment
	 * 还未支付状态
	 * @return bool
	 */
	public function needPayment()
	{
		//
		if( $this->paymentStatus == PaymentStatus::WAIT_PAYMENT ){
			return true;
		}
		return false;
	}
	
	/**
	 * 是否需要 withholld
	 * 还未代扣签约状态
	 * @return bool
	 */
	public function needWithhold()
	{
		//
		if( $this->withholdStatus == WithholdStatus::WAIT_WITHHOLD ){
			return true;
		}
		return false;
	}
	
	/**
	 * 是否需要 fundauth
	 * 还未资金授权状态
	 * @return bool
	 */
	public function needFundauth()
	{
		//
		if( $this->fundauthStatus == FundauthStatus::WAIT_FUNDAUTH ){
			return true;
		}
		return false;
	}
	
	/**
	 * 获取下一个 status 状态
	 * 获取规则顺序：
	 * 第一个环节 支付
	 * 第二个环节 代扣签约
	 * 第三个环节 资金预授权
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return int
	 */
	private function _getNextStatus()
	{
		LogApi::debug('[支付阶段]查找下一个阶段状态');
		$status = 0;
		// 当前环节 支付 
		if( $this->status == PayStatus::WAIT_PAYMENT )
		{
			// 代扣判断
			if( $this->needWithhold() )
			{ // 
				$status = PayStatus::WAIT_WHITHHOLD;
			}
			
			// 预授权判断
			elseif( $this->needFundauth() )
			{
				$status = PayStatus::WAIT_FUNDAUTH;
			}
			// 支付完成
			else{
				$status = PayStatus::SUCCESS;
			}
		}
		// 当前环节 代扣签约 
		elseif( $this->status == PayStatus::WAIT_WHITHHOLD )
		{
			// 预授权判断
			if( $this->needFundauth() )
			{
				$status = PayStatus::WAIT_FUNDAUTH;
			}
			// 支付完成
			else{
				$status = PayStatus::SUCCESS;
			}
		}
		
		// 当前环节 预授权
		elseif( $this->status == PayStatus::WAIT_FUNDAUTH )
		{
			// 支付完成
			$status = PayStatus::SUCCESS;
		}
		// 未找到
		if( $status == 0 ){
			LogApi::error('[支付阶段]查询下一个阶段状态异常');
			throw new \Exception( '获取支付阶段next状态错误' );
		}
		LogApi::debug('[支付阶段]下一个阶段状态为:'.$status);
		return $status;
	}
	
	/**
	 * 回调业务支付状态
	 * @throws \Exception
	 */
	private function _statusCallback( $step )
	{
		// 支付环节完成，但支付阶段还有后续操作时，也回调业务通知
		if( $this->status != PayStatus::SUCCESS
				&& $step == 'payment' )
		{
			LogApi::debug('[支付阶段]回调业务通知(支付环节完成)');
		}
		// 支付阶段完成时，回调业务通知
		elseif( $this->status == PayStatus::SUCCESS )
		{
			LogApi::debug('[支付阶段]回调业务通知(支付阶段完成)');
		}
		// 支付阶段关闭时，回调业务通知
		elseif( $this->status == PayStatus::CLOSED )
		{
			LogApi::debug('[支付阶段]回调业务通知(支付阶段完成)');
		}else{
			LogApi::debug('[支付阶段]跳过业务通知');
		}
	}
}
