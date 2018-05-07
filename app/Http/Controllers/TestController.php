<?php

namespace App\Http\Controllers;

use App\Lib\Common\EmailApi;
use App\Lib\Common\SmsApi;
use App\Lib\Common\JobQueueApi;

class TestController extends Controller
{
	public function testEmail(){
		$b = EmailApi::send(['liuhongxing@huishoubao.com.cn'], 'Test...', 'test test');// ok
		var_dump($b);exit;
	}
	public function testSms(){
		$mobile = '15311371612';
		$b = SmsApi::sendCode($mobile);
		var_dump($b);exit;
	}
	
	public function testJob(){
		
		$callback = 'https://dev-api-zuji.huishoubao.com/test.php';

		$b = false;

		
		$b = JobQueueApi::addRealTime('test-realtime','https://dev-api-zuji.huishoubao.com/test.php', [
			'key' => 'test-realtime',
			'time' => date('Y-m-d H:i:s'),
		],$callback);
		var_dump( 'test-realtime:'. ($b?'ok':'no' ) );
		
		
		$b = JobQueueApi::addScheduleOnce('test-schedule-once','https://dev-api-zuji.huishoubao.com/test.php', [
			'key' => 'test-schedule-once',
			'time' => date('Y-m-d H:i:s'),
		],time(),$callback);
		var_dump( 'test-schedule-once:'. ($b?'ok':'no' ) );
		
		$b = JobQueueApi::addScheduleEvery('test-schedule-every','https://dev-api-zuji.huishoubao.com/test.php', [
			'key' => 'test-schedule-every',
			'time' => date('Y-m-d-H:i:s'),
		],'1s',$callback);
		var_dump( 'test-schedule-every:'. ($b?'ok':'no' ) );
		
		$b = JobQueueApi::addScheduleCron('test-schedule-cron','https://dev-api-zuji.huishoubao.com/test.php', [
			'key' => 'test-schedule-cron',
			'time' => date('Y-m-d H:i:s'),
		],'*/1 * * * *',$callback);
		var_dump( 'test-schedule-cron:'. ($b?'ok':'no' ) );
		
		sleep(4);
		$b = JobQueueApi::disable('test-realtime');
		var_dump( 'test-realtime disable:'. ($b?'ok':'no' ) );
		
		$b = JobQueueApi::disable('test-schedule-once');
		var_dump( 'test-schedule-once disable:'. ($b?'ok':'no' ) );
		
		$b = JobQueueApi::disable('test-schedule-every');
		var_dump( 'test-schedule-every disable:'. ($b?'ok':'no' ) );
		
		$b = JobQueueApi::disable('test-schedule-cron');
		var_dump( 'test-schedule-cron disable:'. ($b?'ok':'no' ) );
		
		
	}
	
	public function test_alipay_url(){
		
		
		$params = [
			'app_id' => '1',	// 业务应用ID
			'out_no' => time(),	// 业务系统支付编号
			'amount' => '1',	// 金额，单位：分
			'name' => '测试商品支付',// 支付名称
			'back_url' => 'https://alipay/Test/notify',
			'front_url' => 'https://alipay/Test/front',
			'fenqi' => 0,	// 分期数
			'user_id' => 5,// 用户ID
		];
		$data = \App\Lib\Payment\Alipay::getUrl($params);
		var_dump( $data );exit;
	}
	
}
