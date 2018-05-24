<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderCleaningStatus;
use App\Order\Modules\Service\OrderCleaning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Lib\Common\LogApi;

/**
 * 支付控制器
 */
class PayController extends Controller
{

    public function __construct()
    {
    }
	
	// 测试 代扣签约
	public function testWithholdSign(){
		
		$business_type = 1; 
		$business_no = 'FA52123851726694';
		try {
			// 查询
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);
			
			$step = $pay->getCurrentStep();
			// echo '当前阶段：'.$step."\n";
			
			$_params = [
				'name'			=> '测试-'.$step,					//【必选】string 交易名称
				'front_url'		=> env('APP_URL').'/order/pay/testWithholdSignFront',	//【必选】string 前端回跳地址
			];
			$url_info = $pay->getCurrentUrl( $_params );
//			var_dump( $_params, $url_info );exit;
			header( 'Location: '.$url_info['url'] );

		} catch (\App\Lib\NotFoundException $exc) {
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			echo $exc->getTraceAsString();
		}
	}
		// 测试 前端回跳
	public function testWithholdSignFront(){
		LogApi::info('代扣签约同步通知', $_GET);
		var_dump( $_GET );exit;
	}
	
	// 测试 代扣解约
	public function testWithholdUnsign(){
		
		$user_id = '';
		
		$info = \App\Lib\Payment\CommonWithholdingApi::unSign([
    		'user_id'		=> '5', //租机平台用户ID
    		'agreement_no'	=> '30A52283670286585', //支付平台签约协议号
    		'out_agreement_no'	=> 'FA52283402712189', //业务平台签约协议号
    		'back_url'		=> env('APP_URL').'/order/pay/withholdUnsignNotify', //后端回调地址
		]);
		
		var_dump( $info );exit;
	}
		
	
	// 测试 退款
	public function testRefund(){
		$info = \App\Lib\Payment\CommonRefundApi::apply([
    		'name'			=> '测试退款',			//交易名称
    		'out_refund_no' => \createNo(1),		//业务系统退款码
    		'payment_no'	=> '10A52223623034601', //支付系统支付码
    		'amount'		=> 1, //支付金额；单位：分
			'refund_back_url'		=> env('APP_URL').'/order/pay/refundNotify',	//【必选】string //退款回调URL
		]);
		var_dump( $info );exit;
	}
	
	/**
	 * 
	 * @param array $_POST
	 * [
	 *		'refund_no'		=> '',	//【必选】string 支付系统退款编号
	 *		'out_refund_no'	=> '',	//【必选】string 业务系统退款编号
	 *		'status'		=> '',	//【必选】string 支付状态； init：初始化； processing：处理中；success：退款成功；failed：退款失败
	 *		'amount'		=> '',	//【必选】int 交易金额； 单位：分
	 *		'reason'		=> '',	//【必选】stirng 失败原因
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function refundNotify()
	{
		$input = file_get_contents("php://input");
		LogApi::info('退款异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
		
	}
	
	
	// 测试 支付
	public function test(){
		
		
		$business_type = 1; 
		$business_no = 'FA52283402709381';
		$pay = null;
		try {
			// 查询
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);
			// 取消
			$pay->cancel();
			// 恢复
			$pay->resume();

		} catch (\App\Lib\NotFoundException $exc) {

			// 创建支付
			$pay = \App\Order\Modules\Repository\Pay\PayCreater::createPaymentWithholdFundauth([
				'user_id'		=> '5',
				'businessType'	=> $business_type,
				'businessNo'	=> $business_no,
				
				'paymentNo' => \createNo(1),
				'paymentAmount' => '0.02',
				'paymentChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
				'paymentFenqi'	=> 0,
				
				'withholdNo' => \createNo(1),
				'withholdChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
				
				'fundauthNo' => \createNo(1),
				'fundauthAmount' => '1.00',
				'fundauthChannel'=> \App\Order\Modules\Repository\Pay\Channel::Alipay,
			]);
		} catch (\Exception $exc) {
			exit('error');
		}
		
		try {
			$step = $pay->getCurrentStep();
			// echo '当前阶段：'.$step."\n";
			
			$_params = [
				'name'			=> '测试支付',					//【必选】string 交易名称
				'front_url'		=> env('APP_URL').'/order/pay/testPaymentFront',	//【必选】string 前端回跳地址
			];
			$url_info = $pay->getCurrentUrl( \App\Order\Modules\Repository\Pay\Channel::Alipay, $_params );
			header( 'Location: '.$url_info['url'] ); 
//			var_dump( $url_info );
			
		} catch (\Exception $exc) {
			echo $exc->getMessage()."\n";
			echo $exc->getTraceAsString();
		}
		
	}
	// 测试 支付前端回跳
	public function testPaymentFront(){
		LogApi::info('支付同步通知', $_GET);
		var_dump( $_GET );exit;
	}
	
	
	/**
	 * 支付异步通知处理
	 * @param array $_POST
	 * [
	 *		'payment_no'		=> '',	//【必选】string 支付系统支付编号
	 *		'out_payment_no'	=> '',	//【必选】string 业务系统支付编号
	 *		'status'		=> '',	//【必选】string 支付状态； init：初始化； processing：处理中；success：支付成功；failed：支付失败
	 *		'amount'		=> '',	//【必选】int 交易金额； 单位：分
	 *		'reason'		=> '',	//【必选】stirng 失败原因
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function paymentNotify()
	{
		$input = file_get_contents("php://input");
		LogApi::info('支付异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
//		$params = [
//			'payment_no'	=> '10A52108092865700',
//			'out_payment_no'=> 'FA52108092585030',
//			'status'		=> 'success',
//			'amount'		=> '1',
//		];
		
		try {
			// 校验支付状态
			$status_info = \App\Lib\Payment\CommonPaymentApi::query($params);
			if( $status_info['status'] != 'success' ){// 支付成功
				echo 'payment status not success';exit;
			}
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByPaymentNo( $params['out_payment_no'] );
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo 'payment notice repeated ';exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needPayment() ){
				echo 'payment not need ';exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 支付处理
			$pay->paymentSuccess([
				'out_payment_no' => $params['payment_no'],
				'payment_time' => time(),
			]);
			
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			echo $exc->getMessage();
		}
		
		DB::rollBack();
		exit;
	}
	
	/**
	 * 代扣签约异步通知处理
	 * @param array $_POST
	 * [
	 *		'agreement_no'		=> '',	//【必选】string 支付系统编号
	 *		'out_agreement_no'	=> '',	//【必选】string 业务系统编号
	 *		'user_id'			=> '',	//【必选】string 业务系统用户ID
	 *		'status'			=> '',	//【必选】string 状态； init：初始化； processing：处理中；signed：签约成功；failed：支付失败
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function withholdSignNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('代扣签约异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
		try {
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByWithholdNo( $params['out_agreement_no'] );
			
			if( $pay->getUserId() != $params['user_id'] ){
				echo 'notice [user_id] not error';exit;
			}
			
			// 校验状态
			$status_info = \App\Lib\Payment\CommonWithholdingApi::queryAgreement([
				'agreement_no' => $params['agreement_no'],
				'out_agreement_no' => $params['out_agreement_no'],
				'user_id' => $pay->getUserId(),
			]);
			// 签约状态
			if( $status_info['status'] != 'signed' ){// 已签约
				echo 'withhold status not signed';exit;
			}
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo 'withhold notice repeated ';exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needWithhold() ){
				echo 'withhold not need ';exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 代扣签约处理
			$pay->withholdSuccess([
				'out_withhold_no' => $params['agreement_no'],	// 支付系统代扣协议编码
			]);
			
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
		} catch (\App\Lib\NotFoundException $exc) {
			echo $exc->getMessage();
		} catch (\Exception $exc) {
			echo $exc->getMessage();
		}
		
		DB::rollBack();
		exit;
	}
	/**
	 * 代扣解约 异步通知
	 * @param array $_POST
	 * [
	 *		'agreement_no'		=> '',	//【必选】string 支付系统编号
	 *		'out_agreement_no'	=> '',	//【必选】string 业务系统编号
	 *		'user_id'			=> '',	//【必选】string 业务系统用户ID
	 *		'status'			=> '',	//【必选】string 状态； init：初始化； processing：处理中；unsigned：解约成功；failed：支付失败
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function withholdUnsignNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('代扣解约异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
		echo '{"status":"ok"}';exit;
	}
	
	
	/**
	 * 预授权冻结异步通知处理
	 * @param array $_POST
	 * [
	 *		'fundauth_no'		=> '',	//【必选】string 支付系统编号
	 *		'out_fundauth_no'	=> '',	//【必选】string 业务系统编号
	 *		'status'		=> '',	//【必选】string 状态； init：初始化； processing：处理中；success：支付成功；failed：支付失败
	 *		'total_amount'		=> '',	//【必选】int 交易金额； 单位：分
	 * ]
	 * 成功时，输出 {"status":"ok"}，其他输出都认为是失败，需要重复通知
	 */
	public function fundauthNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('预授权冻结异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
//		try {
			
			// 查询本地支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByFundauthNo( $params['out_fundauth_no'] );
						
			// 校验状态
			$status_info = \App\Lib\Payment\CommonFundAuthApi::queryFundAuthStatus([
				'fundauth_no' => $params['fundauth_no'],
				'out_fundauth_no' => $params['out_fundauth_no'],
				'user_id' => $pay->getUserId(),
			]);
			// 状态
			if( $status_info['status'] != 'success' ){// 未授权
				echo 'fundauth status not success';exit;
			}
			
			if( $pay->isSuccess() ){// 已经支付成功
				echo 'fundauth notice repeated ';exit;
			}
			
			// 判断是否需要支付
			if( ! $pay->needFundauth() ){
				echo 'fundauth not need ';exit;
			}
			
			// 提交事务
			DB::beginTransaction();
			
			// 代扣签约处理
			$pay->fundauthSuccess([
				'out_fundauth_no' => $params['out_fundauth_no'],	// 支付系统资金预授权编码
				'total_amount' => sprintf('%0.2f',$params['total_amount']/100),
			]);
			
			// 提交事务
            DB::commit();	
			echo '{"status":"ok"}';exit;
			
//		} catch (\App\Lib\NotFoundException $exc) {
//			echo $exc->getMessage();
//		} catch (\Exception $exc) {
//			echo $exc->getMessage();
//		}
		
		DB::rollBack();
		exit;
		
	}
	
	/**
	 * 
	 */
	public function fundautUnfreezeNotify()
	{
		
		$input = file_get_contents("php://input");
		LogApi::info('预授权解冻异步通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'notice data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'notice data not array ';exit;
		}
		
	}
	
}
