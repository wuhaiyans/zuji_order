<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Order\Modules\Repository\Pay\PayCreater;
use App\Order\Modules\Repository\Pay\Channel;
use App\Order\Modules\Repository\Pay\PaymentStatus;
use App\Order\Modules\Repository\Pay\WithholdStatus;
use App\Order\Modules\Repository\Pay\FundauthStatus;

class PayTest extends TestCase
{

    public function testPayALLTest()
    {
		
		$business_type = 1;
		$business_no = \createNo(1);
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
			$pay = PayCreater::createPaymentWithholdFundauth([
				'user_id'		=> '5',
				'businessType'	=> $business_type,
				'businessNo'	=> $business_no,
				
				'paymentNo' => \createNo(1),
				'paymentAmount' => '0.01',
				'paymentChannel'=> Channel::Alipay,
				'paymentFenqi'	=> 0,
				
				'withholdNo' => \createNo(1),
				'withholdChannel'=> Channel::Alipay,
				
				'fundauthNo' => \createNo(1),
				'fundauthAmount' => '1.00',
				'fundauthChannel'=> Channel::Alipay,
			]);
		} catch (\Exception $exc) {
			exit('error');
		}
		
		// 支付阶段状态
		$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );


		// 支付状态
		$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
				'支付环节状态初始化错误' );

		// 代扣签约状态
		$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::WAIT_WITHHOLD,
				'代扣签约状态初始化错误' );

		// 资金预授权状态
		$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::WAIT_FUNDAUTH,
				'资金预授权状态初始化错误' );
		
		try {
			$step = $pay->getCurrentStep();
			echo '当前阶段：'.$step."\n";
			
			$_params = [
				'name'			=> '测试支付',					//【必选】string 交易名称
				'back_url'		=> 'https://alipay/Test/back',	//【必选】string 后台通知地址
				'front_url'		=> 'https://alipay/Test/front',	//【必选】string 前端回跳地址
			];
			$url_info = $pay->getCurrentUrl( $_params );
			var_dump( $url_info );
			
		} catch (\Exception $exc) {
			echo $exc->getMessage()."\n";
			echo $exc->getTraceAsString();
		}

	}

	
//    /**
//     * 测试 payment + withhold + fundauth
//     * @return void
//     */
//    public function testPayALL()
//    {
//			$business_type = 1;
//			$business_no = \createNo(1);
//			
//			// 创建支付
//			$pay = PayCreater::createPaymentWithholdFundauth([
//				'user_id'		=> '5',
//				'businessType'	=> $business_type,
//				'businessNo'	=> $business_no,
//				
//				'paymentNo' => \createNo(1),
//				'paymentAmount' => '0.01',
//				'paymentChannel'=> Channel::Alipay,
//				'paymentFenqi'	=> 0,
//				
//				'withholdNo' => \createNo(1),
//				'withholdChannel'=> Channel::Alipay,
//				
//				'fundauthNo' => \createNo(1),
//				'fundauthAmount' => '1.00',
//				'fundauthChannel'=> Channel::Alipay,
//			]);
//			// 支付阶段状态
//			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//			
//			
//			// 支付状态
//			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
//					'支付环节状态初始化错误' );
//			
//			// 代扣签约状态
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::WAIT_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::WAIT_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//			
//			try {
//				// 查询
//				$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusiness($business_type, $business_no);
//				// 取消
//				$pay->cancel();
//				// 恢复
//				$pay->resume();
//				
//			} catch (\Exception $exc) {
//				echo $exc->getTraceAsString();
//			}
//
//			
//			//-+----------------------------------------------------------------
//			// | 支付环节
//			//-+----------------------------------------------------------------
//			
//			echo '获取支付url地址......';
//			$payment_no = '';		// 业务系统支付编号
//			$out_payment_no = '';	// 支付系统支付编号
//			try {
//				$_params = [
//					'name'			=> '测试支付',					//【必选】string 交易名称
//					'back_url'		=> 'https://alipay/Test/back',	//【必选】string 后台通知地址
//					'front_url'		=> 'https://alipay/Test/front',	//【必选】string 前端回跳地址
//				];
//				//$url_info = $pay->getPaymentUrl( $_params );
//				$url_info = $pay->getCurrentUrl( $_params );
//				var_dump( $url_info );
//				echo "ok\n";
//				$out_payment_no = $url_info['_data']['payment_no'];
//				$payment_no = $url_info['_data']['out_payment_no'];
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//
//			echo "支付状态查询......";
//			try {
//				$payment_query = \App\Lib\Payment\CommonPaymentApi::query([
//					'payment_no' => $out_payment_no,
//					'out_payment_no' => $payment_no,
//				]);
//				//var_dump( $payment_query );
//				echo "ok\n";
//				echo "支付状态：{$payment_query['status']}\n";
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//			
//			
//			echo "支付异步通知处理......";
//			try {
//				// 模拟支付成功操作
//				$pay->paymentSuccess([
//					'out_payment_no' => $out_payment_no,
//					'payment_time' => time(),
//				]);
//				$this->assertTrue( $pay->paymentIsSuccess(), '支付环节支付异常' );
//				echo "ok\n";
//			} catch (\Exception $ex) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//			
//			// 取消
//			$pay->cancel();
//			// 恢复
//			$pay->resume();
//			
//			//-+----------------------------------------------------------------
//			// | 代扣签约
//			//-+----------------------------------------------------------------
//			$agreement_no = '';
//			
//			echo "获取代扣签约地址......";
//			try {
//				// 获取代扣地址
//				$_params = [
//					'name'			=> '测试代扣签约',					//【必选】string 交易名称
//					'back_url'		=> 'https://alipay/Test/back',	//【必选】string 后台通知地址
//					'front_url'		=> 'https://alipay/Test/front',	//【必选】string 前端回跳地址
//				];
//				//$url_info = $pay->getWithholdSignUrl($_params);
//				$url_info = $pay->getCurrentUrl( $_params );
////				$url_info = \App\Lib\Payment\CommonWithholdingApi::getSignUrl([
////					'out_agreement_no'	=> $pay->getWithholdNo(),
////					'channel_type'		=> $pay->getWithholdChannel(),			//【必选】int 支付渠道
////					'name'			=> '测试代扣',					//【必选】string 名称
////					'back_url'		=> 'https://alipay/Test/back',	//【必选】string 后台通知地址
////					'front_url'		=> 'https://alipay/Test/front',	//【必选】string 前端回跳地址
////					'user_id'		=> $pay->getUserId(),			//【可选】int 业务用户ID
////				]);
//				var_dump( $url_info );
//				echo "ok\n";
//				$agreement_no = $url_info['_data']['agreement_no'];
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//			
//			echo "代扣签约异步通知处理......";
//			try {
//				// 代扣签约操作
//				$pay->withholdSuccess([
//					'withhold_no'		=> $agreement_no,			// 支付系统代扣协议编号
//					'out_withhold_no'	=> $pay->getWithholdNo(),	// 业务系统代扣协议编号
//					'user_id'			=> $pay->getUserId(),		//【可选】int 业务用户ID
//				]);
//				$this->assertTrue( $pay->withholdIsSuccess(), '代扣环节状态异常' );
//				echo "ok\n";
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//			
//			echo "代扣签约查询......";
//			try {
//				// 代扣签约查询
//				$agreement_info = \App\Lib\Payment\CommonWithholdingApi::queryAgreement([
//					'agreement_no'		=> $agreement_no,
//					'out_agreement_no'	=> $pay->getWithholdNo(),
//					'user_id'			=> $pay->getUserId(),			//【可选】int 业务用户ID
//				]);
//				//var_dump( $agreement_info );
//				echo "ok\n";
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//			
//			
//			// 取消
//			$pay->cancel();
//			// 恢复
//			$pay->resume();
//			
//			
//			$fundauth_no = '';// 支付系统资金授权编号
//			echo "获取预授权地址......";
//			try {
//				// 获取url
//				$_params = [
//					'name'			=> '测试预授权',					//【必选】string 交易名称
//					'back_url'		=> 'https://alipay/Test/back',	//【必选】string 后台通知地址
//					'front_url'		=> 'https://alipay/Test/front',	//【必选】string 前端回跳地址
//				];
//				//$url_info = $pay->getFundauthUrl($_params);
//				$url_info = $pay->getCurrentUrl( $_params );
////				$url_info = \App\Lib\Payment\CommonFundAuthApi::fundAuthUrl([
////					'out_auth_no'	=> $pay->getFundauthNo(),
////					'channel_type'		=> $pay->getFundauthChannel(),			//【必选】int 支付渠道
////					'amount'		=> $pay->getFundauthAmount()*100,			//【必选】int 预授权金额；单位：分
////					'name'			=> '测试预授权',					//【必选】string 名称
////					'back_url'		=> 'https://alipay/Test/back',	//【必选】string 后台通知地址
////					'front_url'		=> 'https://alipay/Test/front',	//【必选】string 前端回跳地址
////					'user_id'		=> $pay->getUserId(),			//【可选】int 业务平台yonghID
////				]);
//				var_dump( $url_info );
//				echo "ok\n";
//			$fundauth_no = $url_info['_data']['auth_no'];
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//			
//			echo "预授权异步通知处理......";
//			try {
//				// 预授权成功操作
//				$pay->fundauthSuccess([
//					'out_fundauth_no' => $fundauth_no,
//					'total_amount' => $pay->getFundauthAmount(),
//				]);
//				$this->assertTrue( $pay->fundauthIsSuccess(), '预授权环节状态异常' );
//				echo "ok\n";
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//			
//			echo "预授权状态查询......";
//			try {
//				
//				$auth_info = \App\Lib\Payment\CommonFundAuthApi::queryFundAuthStatus([
//					'auth_no'			=> $fundauth_no,			// 支付系统预授权编号
//					'out_auth_no'		=> $pay->getFundauthNo(),	// 业务系统预授权编号
//					'user_id'			=> $pay->getUserId(),			//【可选】int 业务用户ID
//				]);
//				var_dump( $auth_info );
//				echo "ok\n";
//			} catch (\Exception $exc) {
//				echo "error: {$exc->getMessage()}\n";
//				$this->assertTrue(false,$exc->getMessage());
//			}
//    }
    
    
	
//    /**
//     * 测试 withhold 代扣签约
//     * @return void
//     */
//    public function testWithhold()
//    {
//		try {
//			$creater = new \App\Order\Modules\Repository\Pay\PayCreater();
//			
//			// 创建支付
//			// 只有代扣环节，没有其他环节
//			$pay = PayCreater::createWithhold([
//				'businessType' => '1',
//				'businessNo' => \createNo(1),
//				'withholdNo' => \createNo(1),
//				'withholdChannel'=> Channel::Alipay,
//			]);
//			
//			// 支付阶段状态
//			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//			
//			
//			// 支付状态
//			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::NO_PAYMENT,
//					'支付环节状态初始化错误' );
//			
//			// 代扣签约状态
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::WAIT_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::NO_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//			
//			// 获取
//			
//			// 操作
//			$pay->withholdSuccess([
//				'out_withhold_no' => \createNo(1),
//				'uid' => 5,
//			]);
//			$this->assertTrue( $pay->withholdIsSuccess(), '代扣环节状态异常' );
//			
//		} catch (\Exception $ex) {
//			echo $ex->getMessage();
//			$this->assertTrue(false);
//		}
//		
//    }
//	
//	
//    public function testRefund()
//    {
//			// 退款
//			echo '统一退款...';
//			try {
//				$info = \App\Lib\Payment\CommonRefundApi::apply([
//					'out_refund_no' => '201805050009',
//					'payment_no' => '2018050400036',
//					'amount' => '1',
//					'refund_back_url' => '123456',
//				]);
//				$this->assertTrue( is_array($info), '统一退款协议错误' );
//			} catch (\Exception $exc) {
//				echo $exc->getMessage()."\n";
//			}
//
//			echo "统一退款...ok\n";
//		
//	}
//	
//    public function testRefundStatus()
//    {
//			// 查询
//			echo '统一退款查询...';
//			$query_info = \App\Lib\Payment\CommonRefundApi::query([
//				'refund_no' => '11A51920483982018',
//				'out_refund_no' => '201805050009',
//			]);
//			$this->assertTrue( is_array($query_info), '统一支付查询失败' );
//			echo "ok\n";
//			echo "status:{$query_info['status']}\n";
//		
//	}
//	
//	
//    public function testPaymentUrl()
//    {
//		
//		try {
//			// 创建支付
//			// 只有支付环节，没有其他环节
//			$pay = PayCreater::createPayment([
//				'businessType' => '1',
//				'businessNo' => \createNo(1),
//				'paymentNo' => \createNo(1),
//				'paymentAmount' => '0.01',
//				'paymentChannel'=> Channel::Alipay,
//				'paymentFenqi'	=> 3,
//			]);
//			
//			// 支付阶段状态
//			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//			
//			
//			// 支付状态
//			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
//					'支付环节状态初始化错误' );
//			
//			// 代扣签约状态
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::NO_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::NO_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//
//			// 
//			$ApiRequest = new \App\Lib\ApiRequest();
//			$ApiRequest->setUrl('http://dev-order.zuji.com/api');
//			$ApiRequest->setAppid( '1' );	// 模拟客户端 入口ID
//			$ApiRequest->setMethod('api.pay.payment.url');
//			$ApiRequest->setParams([
//				'payment_no' => $pay->getPaymentNo(),	// 模拟业务系统支付编号
//				'front_url' => 'https://alipay/Test/front',// 前端地址
//			]);
//			
//			$Response = $ApiRequest->send();
//
//			$this->assertTrue( $Response->isSuccessed(), '支付链接请求失败:['.$Response->getStatus()->getCode().']'.$Response->getStatus()->getMsg() );
//
//			//
//			$data = $Response->getData();
//			$this->stringStartsWith('https')->evaluate( $data['payment_url'], '支付链接错误' );
//
//			echo '获取支付URL地址... ok'."\n";
//			
//			// 支付平台支付单号
//			$this->assertTrue( !empty($data['_data']['payment_no']), '支付系统编号错误' );
//			
//			// 查询
//			echo '统一支付查询...';
//			$query_info = \App\Lib\Payment\CommonPaymentApi::query([
//				'payment_no' => $data['_data']['payment_no'],
//				'out_payment_no' => $data['_data']['out_no'],
//			]);
//			$this->assertTrue( is_array($query_info), '统一支付查询失败' );
//			echo "ok\n";
//			echo "status:{$query_info['status']}\n";
//			
//			echo '支付... ';
//			// 支付成功操作
//			$pay->paymentSuccess([
//				'out_payment_no' => $data['_data']['out_no'],
//				'payment_time' => time(),
//			]);
//			$this->assertTrue( $pay->paymentIsSuccess(), '支付环节支付异常' );
//			echo 'ok'."\n";
//			
//			// 查询
//			echo '统一支付查询...';
//			$query_info = \App\Lib\Payment\CommonPaymentApi::query([
//				'payment_no' => $data['_data']['payment_no'],
//				'out_payment_no' => $data['_data']['out_no'],
//			]);
//			$this->assertTrue( is_array($query_info), '统一支付查询失败' );
//			echo "ok\n";
//			echo "status:{$query_info['status']}\n";
//			
//			
//		} catch (\Exception $ex) {
//			echo $ex->getMessage();
//			$this->assertTrue(false);
//		}
//		
//    }
//	
//	
//    /**
//     * 测试 payment 支付
//     * @return void
//     */
//    public function testPaymentNotify()
//    {
//		
//		try {
//			$pay_info = \App\Order\Models\OrderPayModel::find(5);
//			$this->assertTrue( !!$pay_info );
//			
//			$pay_info = $pay_info->toArray();
//			
//			// 创建支付
//			// 只有支付环节，没有其他环节
//			$pay = new \App\Order\Modules\Repository\Pay\Pay($pay_info);
//			
//			// 支付阶段状态
//			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//			
//			
//			// 支付状态
//			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
//					'支付环节状态初始化错误' );
//			
//			// 代扣签约状态
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::NO_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::NO_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//			
//			// 取消
//			$pay->cancel();
//			// 恢复
//			$pay->resume();
//			// 取消
//			$pay->cancel();
//			// 恢复
//			$pay->resume();
//			
//			// 支付成功操作
//			$pay->paymentSuccess([
//				'out_payment_no' => createNo(1),
//				'payment_time' => time(),
//			]);
//			$this->assertTrue( $pay->paymentIsSuccess(), '支付环节支付异常' );
//			
//		} catch (\Exception $ex) {
//			echo $ex->getMessage();
//		}
//	}
//	
//    /**
//     * 测试 payment 支付
//     * @return void
//     */
//    public function testPayment()
//    {
//		try {
//			// 创建支付
//			// 只有支付环节，没有其他环节
//			$pay = PayCreater::createPayment([
//				'businessType' => '1',
//				'businessNo' => \createNo(1),
//				'paymentNo' => \createNo(1),
//				'paymentAmount' => '0.01',
//				'paymentChannel'=> Channel::Alipay,
//				'paymentFenqi'	=> 0,
//			]);
//			
//			// 支付阶段状态
//			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//			
//			
//			// 支付状态
//			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
//					'支付环节状态初始化错误' );
//			
//			// 代扣签约状态
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::NO_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::NO_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//			
//			// 支付成功操作
//			$pay->paymentSuccess([
//				'out_payment_no' => createNo(1),
//				'payment_time' => time(),
//			]);
//			$this->assertTrue( $pay->paymentIsSuccess(), '支付环节支付异常' );
//			
//			
//		} catch (\Exception $ex) {
//			echo $ex->getMessage();
//			$this->assertTrue(false);
//		}
//		
//    }
//	
//	
//    /**
//     * 测试 fundauth 资金预授权
//     * @return void
//     */
//    public function testFundauth()
//    {
//		try {
//			$creater = new \App\Order\Modules\Repository\Pay\PayCreater();
//			
//			// 创建支付
//			// 只有预授权环节，没有其他环节
//			$pay = PayCreater::createFundauth([
//				'businessType' => '1',
//				'businessNo' => \createNo(1),
//				'fundauthNo' => \createNo(1),
//				'fundauthAmount' => '1.00',
//				'fundauthChannel'=> Channel::Alipay,
//			]);
//			// 支付阶段状态
//			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//			
//			
//			// 支付状态
//			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::NO_PAYMENT,
//					'支付环节状态初始化错误' );
//			
//			// 代扣签约状态
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::NO_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::WAIT_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//			
//			// 操作
//			$pay->fundauthSuccess([
//				'out_fundauth_no' => \createNo(1),
//				'uid' => 5,
//				'total_amount' => '1.00',
//			]);
//			$this->assertTrue( $pay->fundauthIsSuccess(), '预授权环节状态异常' );
//			
//		} catch (\Exception $ex) {
//			echo $ex->getMessage();
//			echo $ex->getTraceAsString();
//			$this->assertTrue(false);
//		}
//    }
//	
//    /**
//     * 测试 payment + fundauth
//     * @return void
//     */
//    public function testPayPF()
//    {
//		try {
//			$creater = new \App\Order\Modules\Repository\Pay\PayCreater();
//			
//			// 创建支付
//			$pay = PayCreater::createPaymentFundauth([
//				'businessType' => '1',
//				'businessNo' => \createNo(1),
//				
//				'paymentNo' => \createNo(1),
//				'paymentAmount' => '0.01',
//				'paymentChannel'=> Channel::Alipay,
//				'paymentFenqi'	=> 0,
//				
//				'withholdNo' => \createNo(1),
//				'withholdChannel'=> Channel::Alipay,
//				
//				'fundauthNo' => \createNo(1),
//				'fundauthAmount' => '1.00',
//				'fundauthChannel'=> Channel::Alipay,
//			]);
//			// 支付阶段状态
//			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
//			
//			
//			// 支付状态
//			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
//					'支付环节状态初始化错误' );
//			
//			// 代扣签约状态
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::NO_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::WAIT_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//			
//			// 支付成功操作
//			$pay->paymentSuccess([
//				'out_payment_no' => createNo(1),
//				'payment_time' => time(),
//			]);
//			$this->assertTrue( $pay->paymentIsSuccess(), '支付环节支付异常' );
//			
//			// 预授权成功操作
//			$pay->fundauthSuccess([
//				'out_fundauth_no' => \createNo(1),
//				'uid' => 5,
//				'total_amount' => '1.00',
//			]);
//			$this->assertTrue( $pay->fundauthIsSuccess(), '预授权环节状态异常' );
//			
//		} catch (\Exception $ex) {
//			echo $ex->getMessage();
//			echo $ex->getTraceAsString();
//			$this->assertTrue(false);
//		}
//    }
//	
//	
	
}
