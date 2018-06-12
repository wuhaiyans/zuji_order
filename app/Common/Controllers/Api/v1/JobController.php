<?php

namespace App\Common\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Curl;

/**
 * 任务控制器
 */
class JobController extends Controller
{

    public function __construct()
    {
		
    }
	
	/**
	 * 任务生产者
	 */
	public function testJobProducer(){
		LogApi::debug('testJobProducer');
		$url = 'http://dev-order-zuji.huishoubao.com/common/job/testJobCustomer';
		$b = \App\Lib\Common\JobQueueApi::addRealTime('test', $url, ['test'=>'TEST']);
		echo 'Job creation is '. ( $b ? 'ok': 'error');
		exit;
	}
		
	/**
	 * 任务消费者
	 */
	public function testJobCustomer(){
		$input = file_get_contents("php://input");
		LogApi::debug('testJobCustomer', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			echo 'job\'s data is null ';exit;
		}
		if( !is_array($params) ){
			echo 'job\'s data not array ';exit;
		}
		
		echo '{"status":"ok"}';
	}
		
	
}
