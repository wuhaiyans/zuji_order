<?php
/**
 * App\Order\Modules\Repository\Pay\Payment.php
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
 * 直接支付 类
 * <p>直接支付支付成功时创建</p>
 * <p>其他情况禁止修改</p>
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Payment 
{
	
	//-+------------------------------------------------------------------------
	// | 直接支付 数据模型
	//-+------------------------------------------------------------------------
	private $PaymentModel;
	
	
	/**
	 * 创建时间戳
	 * @var int
	 */
	protected $createTime = 0;
	
	/**
	 * 
	 * @param array $data
	 * [
	 *		'payment_no' => '',
	 *		'out_payment_no' => '',
	 * ]
	 */
	public function __construct(array $data=[]) 
	{
		LogApi::debug('[直接支付环节]创建');
		// 直接支付 数据模型
		$this->PaymentModel = new OrderPayPaymentModel();
		if( isset($data['payment_no']) ){
			$this->PaymentModel->payment_no = $data['payment_no'];
		}
		if( isset($data['out_payment_no']) ){
			$this->PaymentModel->out_payment_no = $data['out_payment_no'];
		}
		LogApi::debug('[直接支付环节]创建成功');
	}
		
	public function __destruct() 
	{
		sql_profiler();
		$b = $this->PaymentModel->save();
		var_dump( $b );
	}
	
}
