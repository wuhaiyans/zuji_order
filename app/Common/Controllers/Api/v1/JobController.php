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
	 * 创建扣款定时任务
	 */
	public function createJobForWithholdCreatePay(){
		
		$ids = [
		];
		
		foreach( $ids as $id ){
		
			$params = [
				'sign' => '',
				'sign_type' => 'MD5',
				'params' => [
					'id' => $id,
				],
			];
			// 排序
			ksort( $params['params'] );

			$str = http_build_query( $params['params'] );

			$key = '1234567890';

			// 签名
			$params['sign'] = md5($str.$key);

			$name = 'withhold-create-pay-'.$id;
			LogApi::debug('createJobForWithholdCreatePay');
			$url = 'https://test2-api-zuji.huishoubao.com/api.php?m=crontab&c=instalment&a=instalment_withhold';
			$b = \App\Lib\Common\JobQueueApi::addRealTime($name, $url, $params);
	//		// 10秒钟一次
	//		$b = \App\Lib\Common\JobQueueApi::addScheduleCron($name, $url, ['test'=>'TEST'],'*/10 * * * * ?');
			echo '#'.$id.' Job creation is '. ( $b ? 'ok': 'error')."\n";

		}
		echo "----------\n";
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
