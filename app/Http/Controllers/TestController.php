<?php

namespace App\Http\Controllers;

use \App\Lib\Common\EmailApi;
use \App\Lib\Common\SmsApi;

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
	
	public function index(){
		
	}
	
}
