<?php
/**
 * App\Order\Modules\Repository\Pay\PayCreateCenter.php
 * @access public
 * @author gaobo
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Repository\Pay;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderPayModel;

/**
 * 支付创建器 类
 * 定义 创建支付 方式的接口
 * @access public
 * @author gaobo
 */
class PayCreateCenter {
    
    //基础参数
	private $user_id = 0;
	private $order_no = '';
	private $business_type = 0;
	private $business_no = '';
	private $status = 0;
	private $create_time = 0;
	
	//直付
	private $payment_status = 0;
	private $payment_no = '';
	private $payment_amount = 0.00;
	private $payment_fenqi = 0;
	
	//代扣
	private $withhold_no = '';
	private $withhold_status = 0;
	
	//预授权
	private $fundauth_no = '';
	private $fundauth_status = 0;
	private $fundauth_amount = 0;
	
	//交易号 P W H
	private $trade = '';
	
	//是否继续交易
	private $goingOnPay = false;
	
	//支付优先级
	protected $priority = [PayStatus::WAIT_WHITHHOLD,PayStatus::WAIT_FUNDAUTH,PayStatus::WAIT_PAYMENT];
	
	
	public function setGoingOnPay(bool $goingon_pay)
	{
	    return $this->goingOnPay = $goingon_pay;
	}
	
	public function getGoingOnPay():bool
	{
	    return $this->goingOnPay;
	}
	
	//设置基础信息
	public function setUserId($user_id) : bool
	{
	    return $this->user_id = $user_id;
	}
	public function setOrderNo($order_no) : bool
	{
	    return $this->order_no = $order_no;
	}
	public function setBusinessType($business_type) : bool
	{
	    return $this->business_type = $business_type;
	}
	public function setBusinessNo($business_no) : bool
	{
	    return $this->business_no = $business_no;
	}
	
	/**
	 * 优先级 代扣>预授权>直付
	 * 设置status时 应设置为优先级最高的wait状态
	 * @param unknown $status
	 * @return bool
	 */
	public function setStatus($status) : bool
	{
	    $trans = array_flip($this->priority);
	    if($this->status){
	        $key = $trans[$status];
	        $currentKey = $trans[$this->status];
	        if($key<$currentKey){
	            return $this->status = $status;
	        }
	        return true;
	    }
	    
	    return $this->status = $status;
	}
	
	//设置直付相关参数
	public function setPaymentStatus($payment_status) : bool
	{
	    return $this->payment_status = $payment_status;
	}
	public function setPaymentNo($payment_no) : bool
	{
	    return $this->payment_no = $payment_no;
	}
	public function setPaymentAmount($payment_amount) : bool
	{
	    return $this->payment_amount = $payment_amount;
	}
	public function setPaymentFenqi($payment_fenqi) : bool
	{
	    return $this->payment_fenqi = $payment_fenqi;
	}
	
	//设置代扣相关参数
	public function setWithhold_no($withhold_no) : bool
	{
	    return $this->withhold_no = $withhold_no;
	}
	public function setWithholdStatus($withhold_status) : bool
	{
	    return $this->withhold_status = $withhold_status;
	}
	
	//设置预授权相关参数
	public function setFundauthNo($fundauth_no) : bool
	{
	    return $this->fundauth_no = $fundauth_no;
	}
	public function setFundauthStatus($fundauth_status) : bool
	{
	    return $this->fundauth_status = $fundauth_status;
	}
	public function setFundauthAmount($fundauth_amount) : bool
	{
	    return $this->fundauth_amount = $fundauth_amount;
	}
	
	public function setTrade($trade) : bool
	{
	    return $this->trade .= $trade;
	}
	
	/**
	 * 创建第1种支付方式
	 * <p>普通支付</p>
	 * @param	array	$params		普通支付参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
	 *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
     *
     *      'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
     *      'yiwaixian'         =>'',   //意外险 单位:元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public function create(): Pay
	{
		LogApi::debug('[支付阶段]'.$this->trade.'创建');
		//$params['status'] = PayStatus::WAIT_PAYMENT;
		//$params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;

		$data = [];
		if($this->withhold_status == WithholdStatus::WAIT_WITHHOLD){
		    $data['withhold_no']	 = $this->withhold_no;
		    $data['withhold_status'] = $this->withhold_status;
		    $this->setTrade('W');
		}
		
		if($this->fundauth_status == FundauthStatus::WAIT_FUNDAUTH){
		    $data['fundauth_no']     = $this->fundauth_no;
		    $data['fundauth_status'] = $this->fundauth_status;
		    $data['fundauth_amount'] = $this->fundauth_amount;
		    $this->setTrade('F');
		}
		
		if($this->payment_status == PaymentStatus::WAIT_PAYMENT){
		    $data['payment_status']	= $this->payment_status;
		    $data['payment_no']		= $this->payment_no;
		    $data['payment_amount']	= $this->payment_amount;
		    $data['payment_fenqi']	= $this->payment_fenqi;
		    $this->setTrade('P');
		}
		
		if(empty($data)){
		    LogApi::error('[支付阶段]'.$this->trade.'创建失败',$data);
		    throw new \Exception( '创建支付记录失败' );
		}
        $data['status']		   = $this->status;
		
		$data['user_id']	   = $this->user_id;//可能不需要
	    //$data['order_no']	   = $this->order_no;//可能不需要
        $data['business_type'] = $this->business_type;
        $data['business_no']   = $this->business_no;
        $data['create_time']   = time();

        
        $payModel = new OrderPayModel();
        
		//sql_profiler();
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]'.$this->trade.'创建失败',$data);
			throw new \Exception( '创建支付记录失败' );
		}
		LogApi::debug('[支付阶段]'.$this->trade.'创建成功');
		return new Pay($data);
	}
	
	public function update(): Pay
	{
	    LogApi::debug('[支付阶段]'.$this->trade.'更新');
	    // 更新 支付阶段 表
	    $payModel = new OrderPayModel();
	    $payment_no = \creage_payment_no();
	    $b = $payModel->limit(1)->where([
	        'business_type'	=> $this->business_type,
	        'business_no'	=> $this->business_no,
	    ])->update([
	        'payment_no' => $payment_no,
	    ]);
	    if( !$b ){
	        LogApi::error('[支付阶段]'.$this->trade.'更新失败');
	        throw new \Exception( '更新失败' );
	    }
	    $this->payment_no = $payment_no;
	    LogApi::debug('[支付阶段]'.$this->trade.'更新成功');
	    
	    $data = [];
	    if($this->withhold_status == WithholdStatus::WAIT_WITHHOLD){
	        $data['withhold_no']	 = $this->withhold_no;
	        $data['withhold_status'] = $this->withhold_status;
	        $this->setTrade('W');
	    }
	    
	    if($this->fundauth_status == FundauthStatus::WAIT_FUNDAUTH){
	        $data['fundauth_no']     = $this->fundauth_no;
	        $data['fundauth_status'] = $this->fundauth_status;
	        $data['fundauth_amount'] = $this->fundauth_amount;
	        $this->setTrade('F');
	    }
	    
	    if($this->payment_status == PaymentStatus::WAIT_PAYMENT){
	        $data['payment_status']	= $this->payment_status;
	        $data['payment_no']		= $this->payment_no;
	        $data['payment_amount']	= $this->payment_amount;
	        $data['payment_fenqi']	= $this->payment_fenqi;
	        $this->setTrade('P');
	    }
	    
	    $data['status']		   = $this->status;
	    
	    $data['user_id']	   = $this->user_id;//可能不需要
	    //$data['order_no']	   = $this->order_no;//可能不需要
	    $data['business_type'] = $this->business_type;
	    $data['business_no']   = $this->business_no;
	    $data['update_time']   = time();
	    return new Pay($data);
	}

	
	/**
	 * 创建第2种支付方式
	 * <p>代扣签约</p>
	 * @param	array	$params		签约参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createWithhold( $params ): Pay{
		LogApi::debug('[支付阶段]W创建');
		$params['status'] = PayStatus::WAIT_WHITHHOLD;
		$params['withholdStatus'] = WithholdStatus::WAIT_WITHHOLD;
		
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'withhold_no'		=> \creage_withhold_no(),
			'withhold_status'	=> $params['withholdStatus'],
		];
		//sql_profiler()
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]W创建失败',$data);
			throw new \Exception( '创建支付记录失败' );
		}
		LogApi::debug('[支付阶段]W创建成功');
		return new Pay($data);
	}
	
	/**
	 * 创建第3种支付方式
	 * <p>资金预授权</p>
	 * @param	array	$params		资金预授权参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createFundauth( array $params ): Pay{
		
		LogApi::debug('[支付阶段]F创建');
		$params['status'] = PayStatus::WAIT_FUNDAUTH;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
		];
		//sql_profiler();
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]F创建失败',$data);
			throw new \Exception( '创建资金预授权记录失败' );
		}
		LogApi::debug('[支付阶段]F创建成功');
		return new Pay( $data );
	}
	
	/**
	 * 创建第4种支付方式
	 * <p>代扣签约+资金预授权</p>
	 * @param	array	$params		参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * 
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createWithholdFundauth( array $params ): Pay
	{
		$params['status'] = PayStatus::WAIT_WHITHHOLD;
		$params['withholdStatus'] = WithholdStatus::WAIT_WITHHOLD;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		LogApi::debug('[支付阶段]WF创建');
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'withhold_no'		=> \creage_withhold_no(),
			'withhold_status'	=> $params['withholdStatus'],
			
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
		];
		//sql_profiler();
		$b = $payModel->insert($data);
		if( !$b )
		{
			LogApi::debug('[支付阶段]WF创建失败',$data);
			throw new \Exception( '创建失败' );
		}
		LogApi::debug('[支付阶段]WF创建成功',$data);
		return new Pay( $data );
	}
	
	/**
	 * 创建第5种支付方式 先预授权 再支付
	 * <p>普通支付+资金预授权</p>
	 * @param	array	$params		参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * 
	 *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
	 *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
	 * 
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createPaymentFundauth( array $params ): Pay
	{
		
		$params['status'] = PayStatus::WAIT_FUNDAUTH;
		$params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		LogApi::debug('[支付阶段]PF创建');
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'payment_status'	=> $params['paymentStatus'],
			'payment_no'		=> \creage_payment_no(),
			'payment_amount'	=> $params['paymentAmount'],
			'payment_fenqi'		=> $params['paymentFenqi'],
						
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
		];
		//sql_profiler();
		$b = $payModel->insert($data);
		if( !$b ){
			LogApi::error('[支付阶段]PF创建失败',$data);
			throw new \Exception( '创建失败' );
		}
		LogApi::debug('[支付阶段]PF创建成功',$data);
		return new Pay( $data );
	}
	
	/**
	 * 创建第6种支付方式
	 * <p>普通支付+代扣签约+资金预授权</p>
	 * @param	array	$params		参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * 
	 *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
	 *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
	 * 			
	 *		'fundauthNo'		=> '',	// 预授权编号
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createPaymentWithholdFundauth( array $params ): Pay
	{
		// 状态
		$params['status'] = PayStatus::WAIT_WHITHHOLD;
		$params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;
		$params['withholdStatus'] = WithholdStatus::WAIT_WITHHOLD;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		LogApi::debug('[支付阶段]创建PWF');
		$payModel = new OrderPayModel();
		//sql_profiler();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'payment_status'	=> $params['paymentStatus'],
			'payment_no'		=> \creage_payment_no(),
			'payment_amount'	=> $params['paymentAmount'],
			'payment_fenqi'		=> $params['paymentFenqi'],
			
			'withhold_no'		=> \creage_withhold_no(),
			'withhold_status'	=> $params['withholdStatus'],
			
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
		];
		//sql_profiler();
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]PWF创建失败',$data);
			throw new \Exception( '创建失败' );
		}
		LogApi::debug('[支付阶段]PWF创建成功',$data);
		return new Pay( $data );
	}
    /**
     * 创建第7种支付方式
     * <p>乐百分支付</p>
     * @param	array	$params		普通支付参数
     * [
     *		'userId'			=> '',	// 业务用户ID
     *		'orderNo'			=> '',	// 订单编号
     *		'businessType'		=> '',	// 业务类型
     *		'businessNo'		=> '',	// 业务编号
     *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
     *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
     *
     *      'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
     *      'yiwaixian'         =>'',   //意外险 单位:元
     * ]
     * @return \App\Order\Modules\Repository\Pay\Pay
     */
    public static function createLebaifenPayment( array $params ): Pay{
        LogApi::debug('[支付阶段]P创建');
        $params['status'] = PayStatus::WAIT_PAYMENT;
        $params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;
        $params['yiwaixian']  =isset($params['yiwaixian'])?$params['yiwaixian']:0.00;
        $paymentAmountBillList =[
            'zujin'=>normalizeNum($params['paymentAmount']-$params['yiwaixian']),
            'yajin'=>$params['fundauthAmount'],
            'yiwaixian'=>$params['yiwaixian'],
        ];

        $payModel = new OrderPayModel();
        $data = [
            'user_id'		=> $params['userId'],
            'order_no'		=> $params['orderNo'],
            'business_type'	=> $params['businessType'],
            'business_no'	=> $params['businessNo'],
            'status'		=> $params['status'],
            'create_time'	=> time(),

            'payment_status'	=> $params['paymentStatus'],
            'payment_no'		=> \creage_payment_no(),
            'payment_amount'	=> $params['paymentAmount']+$params['fundauthAmount'],
            'payment_fenqi'		=> $params['paymentFenqi'],

            'payment_amount_bill_list' =>json_encode($paymentAmountBillList),

        ];

        //sql_profiler();
        $b = $payModel->insert( $data );
        if( !$b ){
            LogApi::error('[支付阶段]P创建失败',$data);
            throw new \Exception( '创建支付记录失败' );
        }
        LogApi::debug('[支付阶段]P创建成功');
        return new Pay($data);
    }


}
