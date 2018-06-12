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
//		\App\Lib\Common\JobQueueApi::addRealTime('test', $url, $data)
		var_dump(123);exit;
	}
		
	/**
	 * 任务消费者
	 */
	public function testJobCustomer(){
		LogApi::debug('test');
		var_dump(123);exit;
	}
		
	
}
