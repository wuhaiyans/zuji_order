<?php

namespace App\Order\Controllers\Api\v1;
use App\Lib\Common\LogApi;


/**
 * 支付控制器
 */
class NoticeController extends Controller
{

    public function __construct()
    {
    }
	
	public function test(){
		
		// 通知器
		$notice = new \App\Order\Modules\Service\OrderNotice( '1', 'A522161963164886', 'order_create' );
		
//		$b = $notice->asyncNotify();
		$b = $notice->notify();
		
		var_dump( $b );exit;
	}
	
	/**
	 * 订单通知
	 */
	public function notify(){
		
		$input = file_get_contents("php://input");
		
		LogApi::debug('订单通知', $input);
		
		$params = json_decode($input,true);
		if( is_null($params) ){
			$this->error($params, '输入错误[json]');
		}
		if( !is_array($params) ){
			$this->error($params, '输入错误[array]');
		}
		
		if( !isset( $params['business_type'] ) ){
			$this->error($params, '输入错误[business_type]');
		}
		if( !isset( $params['business_no'] ) ){
			$this->error($params, '输入错误[business_no]');
		}
		if( !isset( $params['scene'] ) ){
			$this->error($params, '输入错误[scene]');
		}
		
		// 通知器
		$notice = new \App\Order\Modules\Service\OrderNotice( 
				$params['business_type'],
				$params['business_no'],
				$params['scene'] );
		
		// 通知渠道
		isset( $params['channel'] ) && $notice->setChannel( $params['channel'] );
		
		// 发送通知
		$b = $notice->notify();
		
		if( !$b ){
			$this->error($params, '通知失败');
		}
		
		echo json_encode([
			'status' => 'ok',
		]);exit;
	}
	
	/**
	 * 输出错误
	 * @param array $params
	 * @param string $error
	 */
	private function error( $params, string $error){
		
		echo json_encode([
			'error' => $error,
			'params' => $params,
		]);
		exit;
	}

}
