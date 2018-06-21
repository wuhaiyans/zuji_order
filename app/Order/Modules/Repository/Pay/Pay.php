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
 * <li>直接支付</li>
 * <li>代扣签约</li>
 * <li>资金预授权</li>
 * </ul>
 * <p>注：每个环节都是可选的，但至少存在一个环节（支付阶段才有意义）</p>
 * <p>注：每个环节在处理完成后，禁止再做任何数据修改了</p>
 * paymentSuccess(array $params) 用于支付完成时调用，进入下一个状态
 * withholdSuccess(array $params) 用于代扣签约完成时调用，进入下一个状态
 * fundauthSuccess(array $params) 用于资金预授权完成时调用，进入下一个状态
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Pay extends \App\Lib\Configurable 
{
	
	/**
	 * 主键
	 * @var string
	 */
	protected $id = 0;
	//-+------------------------------------------------------------------------
	// | 
	//-+------------------------------------------------------------------------
	
	protected $user_id = 0;
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
	
	/**
	 * 用于支付系统交易名称
	 * @var 交易名称
	 */
	protected $name = '';
	
	//-+------------------------------------------------------------------------
	// | 支付相关
	//-+------------------------------------------------------------------------
	protected $paymentStatus = 0;
	protected $paymentChannel = 0;
	protected $paymentAmount = 0.00;
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
	
	
	//-+------------------------------------------------------------------------
	// | 属性相关 setter 和 getter
	//-+------------------------------------------------------------------------
	public function getUserId(){
		return $this->user_id;
	}
	
	public function getPaymentNo(){
		return $this->paymentNo;
	}
	public function getPaymentAmount(){
		return $this->paymentAmount;
	}
	public function getPaymentFenqi(){
		return $this->paymentFenqi;
	}
	public function getPaymentChannel(){
		return $this->paymentChannel;
	}
	
	public function setPaymentAmount( $amount ){
		$this->paymentAmount = $amount;
		return $this;
	}
	
	public function getWithholdNo(){
		return $this->withholdNo;
	}
	public function getWithholdChannel(){
		return $this->withholdChannel;
	}
	
	public function getFundauthNo(){
		return $this->fundauthNo;
	}
	public function getFundauthChannel(){
		return $this->fundauthChannel;
	}
	public function getFundauthAmount(){
		return $this->fundauthAmount;
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
	
	//-+------------------------------------------------------------------------
	// | 业务相关
	//-+------------------------------------------------------------------------
	/**
	 * 支付阶段 是否完成
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function isSuccess(){
		return $this->status == PayStatus::SUCCESS;
	}
	
	/**
	 * 获取 当前环节
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return string
	 * @throws \Exception	发生错误时抛出异常
	 */
	public function getCurrentStep( ){
		if( $this->isSuccess() ){
			throw new \Exception('支付单已完成');
		}
		if( $this->needPayment() ){
			return 'payment';
		}elseif( $this->needWithhold() ){
			return 'withhold_sign';
		}elseif( $this->needFundauth() ){
			return 'fundauth';
		}
		throw new \Exception('支付单内部错误');
	}
	
	/**
	 * 获取 当前环节 跳转URL地址
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param int		$channel	支付渠道
	 * @param array		$params		请求参数
	 * [
	 *		'name'			=> '',	// 交易名称
	 *		'front_url'		=> '',	// 前端回跳地址
	 * ]
	 * @return array			返回参数
	 * [
	 *		'url'		=> '',	// 跳转地址
	 *		'params'	=> '',	// 跳转附件参数
	 * ]
	 * @throws \Exception	发生错误时抛出异常
	 */
	public function getCurrentUrl( int $channel, array $params ){
		if( $this->isSuccess() ){
			throw new \Exception('支付单已完成');
		}
		print_r($this->needFundauth());die;
		if( $this->needPayment() ){
			return $this->getPaymentUrl($channel,$params);
		}elseif( $this->needWithhold() ){
			return $this->getWithholdSignUrl($channel,$params);
		}elseif( $this->needFundauth() ){
			return $this->getFundauthUrl($channel,$params);
		}
		throw new \Exception('支付单内部错误');
	}
	
	/**
	 * 是否需要 支付环节
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
	 * 支付环节 是否付成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function paymentIsSuccess(){
		return $this->paymentStatus == PaymentStatus::PAYMENT_SUCCESS;
	}
	
	/**
	 * 是否需要 代扣签约环节
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
	 * 代扣签约环节 是否成功
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public function withholdIsSuccess(){
		return $this->withholdStatus == WithholdStatus::SIGNED;
	}
	
	/**
	 * 是否需要 资金预授权环节
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
	 * 资金预授权环节 是否成功
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
	 * @throws \Exception 失败是抛出异常
	 */
	public function cancel(){
		LogApi::debug('[支付阶段]取消');
		if( $this->status == PayStatus::CLOSED ){
			LogApi::debug('[支付阶段]取消操作重复');
			throw new \Exception('支付取消失败：重复操作');
		}
		
		// 更新 支付阶段 表
		$payModel = new OrderPayModel();
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'status' => PayStatus::CLOSED,
		]);
		if( !$b ){
			LogApi::error('[支付阶段]取消状态保存失败');
			throw new \Exception( '取消失败' );
		}
		
		LogApi::debug('[支付阶段]取消成功');
		$this->status = PayStatus::CLOSED;
		return true;
	}
	
	/**
	 * 恢复
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 * @throws \Exception 失败是抛出异常
	 */
	public function resume(){
		LogApi::debug('[支付阶段]恢复');
		if( $this->status != PayStatus::CLOSED ){
			LogApi::error('[支付阶段]状态错误');
			throw new \Exception('恢复失败');
		}
		
		// 支付判断
		if( $this->needPayment() )
		{ // 
			$status = PayStatus::WAIT_PAYMENT;
		}
		// 代扣判断
		elseif( $this->needWithhold() )
		{ // 
			$status = PayStatus::WAIT_WHITHHOLD;
		}
		// 预授权判断
		elseif( $this->needFundauth() )
		{
			$status = PayStatus::WAIT_FUNDAUTH;
		}else{
			LogApi::error('[支付阶段]状态错误');
			throw new \Exception('恢复失败');
		}
		
		// 更新 支付阶段 表
		$payModel = new OrderPayModel();
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'status' => $status,
		]);
		if( !$b ){
			LogApi::error('[支付阶段]恢复状态保存失败');
			throw new \Exception( '恢复失败' );
		}
		
		LogApi::debug('[支付阶段]恢复成功');
		$this->status = $status;
		return true;
	}
	
	/**
	 * 支付环节 完成处理
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array		$params		支付成功参数
	 * [
	 *		'out_payment_no'	=> '',	// 支付系统支付码
	 *		'payment_amount'	=> '',	// 支付金额；单位元
	 *		'payment_channel'	=> '',	// 支付渠道
	 *		'payment_time'	=> '',	// 支付时间
	 * ]
	 * @return bool
	 * @throws \Exception 失败是抛出异常
	 */
	public function paymentSuccess( array $params ):bool
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
			'payment_channel' => $params['payment_channel'],
			'payment_amount' => $params['payment_amount'],
			'update_time'		=> $update_time,
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
	 *		'withhold_channel'	=> '',	// 支付渠道
	 * ]
	 * @return bool
	 * @throws \Exception
	 */
	public function withholdSuccess( array $params ):bool
	{
		$update_time = time();
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
			'withhold_channel' => $params['withhold_channel'],//
			'update_time'		=> $update_time,
		]);
		if( !$b ){
			LogApi::error('[支付阶段]代扣签约环节处理保存失败1');
			throw new \Exception( '代扣签约环节完成保存失败' );
		}
		// 更新 代扣签约环节 表
		$withholdModel = new OrderPayWithholdModel();
		$b = $withholdModel->insert([
			'withhold_no'		=> $this->withholdNo,
			'withhold_channel'	=> $params['withhold_channel'],
			'out_withhold_no'	=> $params['out_withhold_no'],
			'user_id'			=> $this->user_id,
			'withhold_status'	=> WithholdStatus::SIGNED,// 已签约
			'sign_time'			=> $update_time,
			'update_time'		=> $update_time,
			'counter'			=> 1, // 计数
		]);
		if( !$b ){
			LogApi::error('[支付阶段][代扣签约]保存失败');
			throw new \Exception( '[支付阶段][代扣签约]保存失败' );
		}
		
		// 代扣协议绑定业务
		$withhold = \App\Order\Modules\Repository\Pay\WithholdQuery::getByWithholdNo(
			$this->withholdNo
		);
		$withhold->bind([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		]);
		if( !$b ){
			LogApi::error('[支付阶段][代扣签约]业务绑定失败');
			throw new \Exception( '[支付阶段][代扣签约]业务绑定失败' );
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
	 *		'fundauth_channel'	=> '',	// 支付渠道
	 *		'out_fundauth_no'	=> '',	// 支付系统授权码
	 *		'total_amount'		=> '',	// 预授权金额；单位：元
	 * ]
	 * @return bool
	 * @throws \Exception 失败是抛出异常
	 */
	public function fundauthSuccess( array $params ):bool
	{
		if( $this->status != PayStatus::WAIT_FUNDAUTH 
				|| $this->fundauthStatus != FundauthStatus::WAIT_FUNDAUTH ){
			throw new \Exception('资金预授权环节状态错误');
		}
		
		if( $params['total_amount'] != $this->fundauthAmount ){
			throw new \Exception('资金预授权金额错误');
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
			'fundauth_channel' => $params['fundauth_channel'],
			'update_time'		=> $update_time,
		]);
		if( !$b ){
			throw new \Exception( '预授权环节完成保存失败' );
		}
		// 更新 预授权环节 表
		$fundauthModel = new OrderPayFundauthModel();
		$b = $fundauthModel->insert([
			'fundauth_no' => $this->fundauthNo,
			'out_fundauth_no' => $params['out_fundauth_no'],
			'user_id' => $this->user_id,
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
	
	
	
	
	//-+------------------------------------------------------------------------
	// | 链接地址
	//-+------------------------------------------------------------------------
	
	/**
	 * 获取支付跳转地址和参数
	 * @param int		$channel	支付渠道
	 * @param array				支付请求参数
	 * [
	 *		'name'			=> '',	// 交易名称
	 *		'payment_amount'=> '',	// 交易名称
	 *		'back_url'		=> '',	// 后台通知地址
	 *		'front_url'		=> '',	// 前端回跳地址
	 * ]
	 * @return array 
	 * [
	 *		'url'		=> '',	// 跳转地址
	 *		'params'	=> '',	// 跳转附件参数
	 * ]
	 * @throws \Exception	失败时抛出异常
	 */
	public function getPaymentUrl( int $channel,array $params ){
		
		// 设置支付渠道
		$payModel = new OrderPayModel( );
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'payment_channel' => $channel,
			'update_time' => time(),
		]);
		if( !$b ){
			throw new \Exception( '支付环节支付渠道设置失败' );
		}
		// 获取url
		$url_info = \App\Lib\Payment\CommonPaymentApi::pageUrl([
			'out_payment_no'	=> $this->getPaymentNo(),	//【必选】string 业务支付唯一编号
			'payment_amount'	=> $this->getPaymentAmount()*100,//【必选】int 交易金额；单位：分
			'payment_fenqi'		=> $this->getPaymentFenqi(),	//【必选】int 分期数
			'channel_type'	=> $channel,						//【必选】int 支付渠道
			'user_id'		=> $this->getUserId(),			//【可选】int 业务平台yonghID
			'name'			=> $params['name'],				//【必选】string 交易名称
			'front_url'		=> $params['front_url'],		//【必选】string 前端回跳地址
			//【必选】string 后台通知地址		
			'back_url'		=> env('APP_URL').'/order/pay/paymentNotify',
		]);
		return $url_info;
	}
	
	/**
	 * 获取 预授权 跳转地址和参数
	 * @param int		$channel	支付渠道
	 * @param array				签约请求参数
	 * [
	 *		'name'			=> '',	// 交易名称
	 *		'back_url'		=> '',	// 后台通知地址
	 *		'front_url'		=> '',	// 前端回跳地址
	 * ]
	 * @return array 
	 * [
	 *		'url'		=> '',	// 跳转地址
	 *		'params'	=> '',	// 跳转附件参数
	 * ]
	 * @throws \Exception	失败时抛出异常
	 */
	public function getFundauthUrl( int $channel,array $params ){
		
		// 设置 预授权渠道
		$payModel = new OrderPayModel( );
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'fundauth_channel' => $channel,
			'update_time' => time(),
		]);
		if( !$b ){
			throw new \Exception( '预授权环节支付渠道设置失败' );
		}
		// 获取url
		$url_info = \App\Lib\Payment\CommonFundAuthApi::fundAuthUrl([
			'out_fundauth_no'	=> $this->getFundauthNo(),
			'channel_type'	=> $channel,						//【必选】int 支付渠道
			'amount'		=> $this->getFundauthAmount()*100,	//【必选】int 预授权金额；单位：分
			'user_id'		=> $this->getUserId(),				//【可选】int 业务平台yonghID
			'name'			=> $params['name'],					//【必选】string 交易名称
			'front_url'		=> $params['front_url'],			//【必选】string 前端回跳地址
			//【必选】string 后台通知地址	
			'back_url'		=> env('APP_URL').'/order/pay/fundauthNotify',
		]);
		return $url_info;
	}
	
	/**
	 * 获取 代扣 签约跳转地址和参数
	 * @param int		$channel	支付渠道
	 * @param array				签约请求参数
	 * [
	 *		'name'			=> '',	// 交易名称
	 *		'back_url'		=> '',	// 后台通知地址
	 *		'front_url'		=> '',	// 前端回跳地址
	 * ]
	 * @return array 
	 * [
	 *		'url'		=> '',	// 跳转地址
	 *		'params'	=> '',	// 跳转附件参数
	 * ]
	 * @throws \Exception	失败时抛出异常
	 */
	public function getWithholdSignUrl( int $channel,array $params ){
		
		// 设置 代扣渠道
		$payModel = new OrderPayModel( );
		$b = $payModel->limit(1)->where([
			'business_type'	=> $this->businessType,
			'business_no'	=> $this->businessNo,
		])->update([
			'withhold_channel' => $channel,
			'update_time' => time(),
		]);
		if( !$b ){
			LogApi::type('data-save');
			LogApi::error('代扣签约环节支付渠道设置失败',[
				'business_type'	=> $this->businessType,
				'business_no'	=> $this->businessNo,
				'data' => [
					'withhold_channel' => $channel,
					'update_time' => time(),
				]
			]);
			throw new \Exception( '代扣签约环节支付渠道设置失败' );
		}
		// 获取url
		$url_info = \App\Lib\Payment\CommonWithholdingApi::getSignUrl([
			'out_agreement_no'	=> $this->getWithholdNo(),
			'channel_type'	=> $channel,					//【必选】int 支付渠道
			'user_id'		=> $this->getUserId(),			//【可选】int 业务平台yonghID
			'name'			=> $params['name'],				//【必选】string 交易名称
			'front_url'		=> $params['front_url'],		//【必选】string 前端回跳地址
			//【必选】string 后台通知地址		
			'back_url'		=> env('APP_URL').'/order/pay/withholdSignNotify',
		]);
		return $url_info;
	}
	
	//-+------------------------------------------------------------------------
	// | 私有方法
	//-+------------------------------------------------------------------------
	
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
			$call = $this->_getBusinessCallback();
			if( !is_callable( $call ) ){
				LogApi::type('callback-error')::error('[支付阶段]回调业务通知(支付环节完成)');
				throw new \Exception( '支付回调设置不可调用-'.$call );
			}
			$b = $call( [
				'business_type' => $this->businessType,
				'business_no' => $this->businessNo,
				'status' => 'processing',
			] );
			LogApi::debug('[支付阶段]回调业务通知(支付环节完成)');
			if( !$b ){
				LogApi::type('callback-error')::error('[支付阶段]回调业务通知(支付环节完成)');
				throw new \Exception('支付通知业务回调处理失败');
			}
		}
		// 支付阶段完成时，回调业务通知
		elseif( $this->status == PayStatus::SUCCESS )
		{
			$call = $this->_getBusinessCallback();
			if( !is_callable( $call ) ){
				LogApi::type('callback-error')::error('[支付阶段]回调业务通知(支付阶段完成)');
				throw new \Exception( '支付回调设置不可调用-'.$call );
			}
			$b = $call( [
				'business_type' => $this->businessType,
				'business_no' => $this->businessNo,
				'status' => 'success',
			] );
			LogApi::debug('[支付阶段]回调业务通知(支付阶段完成)');
			if( !$b ){
				LogApi::type('callback-error')::error('[支付阶段]回调业务通知(支付阶段完成)');
				throw new \Exception('支付通知业务回调处理失败');
			}
		}
		// 支付阶段关闭时，回调业务通知
		elseif( $this->status == PayStatus::CLOSED )
		{
			LogApi::debug('[支付阶段]回调业务通知(支付阶段关闭)');
		}else{
			LogApi::debug('[支付阶段]跳过业务通知');
		}
	}
	
	/**
	 * 
	 */
	private function _getBusinessCallback()
	{
		$callbacks = config('pay_callback.payment');
		if( isset($callbacks[$this->businessType]) && $callbacks[$this->businessType] ){
			return $callbacks[$this->businessType];
		}
		LogApi::error('[支付阶段]业务未设置回调通知');
	}


}
