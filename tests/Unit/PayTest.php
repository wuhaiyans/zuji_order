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
	
    /**
     * 测试 payment 支付
     * @return void
     */
    public function testPayment()
    {
		try {
			$creater = new \App\Order\Modules\Repository\Pay\PayCreater();
			
			// 创建支付
			// 只有支付环节，没有其他环节
			$pay = PayCreater::createPayment([
				'businessType' => '1',
				'businessNo' => \createNo(1),
				'paymentNo' => \createNo(1),
				'paymentAmount' => '0.01',
				'paymentChannel'=> Channel::Alipay,
				'paymentFenqi'	=> 3,
			]);
			
			// 支付阶段状态
			$this->assertFalse( $pay->isSuccess(), '支付阶段状态错误' );
			
			
			// 支付状态
			$this->assertTrue( $pay->getPaymentStatus() == PaymentStatus::WAIT_PAYMENT,
					'支付环节状态初始化错误' );
			
			// 代扣签约状态
			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::NO_WITHHOLD,
					'代扣签约状态初始化错误' );
			
			// 资金预授权状态
			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::NO_FUNDAUTH,
					'资金预授权状态初始化错误' );
			
			// 支付成功操作
			$pay->paymentSuccess([
				'out_payment_no' => createNo(1),
				'payment_time' => time(),
			]);
			$this->assertTrue( $pay->paymentIsSuccess(), '支付环节支付异常' );
			
			
		} catch (\Exception $ex) {
			echo $ex->getMessage();
			$this->assertTrue(false);
		}
		
    }
//	
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
//    /**
//     * 测试 payment + withhold + fundauth
//     * @return void
//     */
//    public function testPayALL()
//    {
//		try {
//			$creater = new \App\Order\Modules\Repository\Pay\PayCreater();
//			
//			// 创建支付
//			$pay = PayCreater::createPaymentWithholdFundauth([
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
//			$this->assertTrue(  $pay->getWithholdStatus() == WithholdStatus::WAIT_WITHHOLD,
//					'代扣签约状态初始化错误' );
//			
//			// 资金预授权状态
//			$this->assertTrue( $pay->getFundauthStatus() == FundauthStatus::WAIT_FUNDAUTH,
//					'资金预授权状态初始化错误' );
//			
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
//			
//			// 代扣签约操作
//			$pay->withholdSuccess([
//				'out_withhold_no' => \createNo(1),
//				'uid' => 5,
//			]);
//			$this->assertTrue( $pay->withholdIsSuccess(), '代扣环节状态异常' );
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
	
}
