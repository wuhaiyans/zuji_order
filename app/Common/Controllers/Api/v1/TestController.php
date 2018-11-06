<?php

namespace App\Common\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Curl;

/**
 * 测试控制器
 */
class TestController extends Controller
{

    public function __construct()
    {
    }
	
	public function testLog(){
		LogApi::error('test','test-Abc');
	}
	
    public function test_yajin()
    {
		$params = [
			'user_id'		=> '5',
			'yajin'			=> '900000',
			'market_price'	=> '800000',
		];
		
		try{
			$result = \App\Lib\Risk\Yajin::calculate($params);
			
		} catch (\App\Lib\ApiException $ex) {
			echo $ex->getOriginalValue();exit;
			var_dump( $ex->getOriginalValue() );exit;
		}
		
		var_dump( $result );exit;
	}
		
	
    public function test()
    {
		if( count($_FILES) ){
			
			var_dump( $_FILES );exit;
		}
		try{
			
			$accountId = 'DCODMVCN';
			$entity = [
				// 用户类型；固定值；ENTERPRISE：企业实体
				'userType'	=> 'ENTERPRISE',
				// 企业名称
				'certName'	=> '深圳回收宝科技有限公司',
				// 证件类型；UNIFIED_SOCIAL_CREDIT_CODE：统一社会信用代码
				'certType'	=> 'UNIFIED_SOCIAL_CREDIT_CODE',
				// 证件号
				'certNo'	=> '91440300311802545U',
				'mobileNo'	=> '',
				// 企业法人
				'legalPerson'	=> '何帆',
				// 企业法人身份证号
				'legalPersonId'	=> '420102198108011012',	
				// 经办人姓名
				'agent'		=> '赵明亮',	
				// 经办人身份证
				'agentId'	=> '232301199005211535',
				// 扩展参数
				'properties' => '',
			];
			
			// 用户信息
			$customer = new \App\Lib\Alipay\Notary\CustomerIdentity();
			$customer->setCertNo('130423198906021038');
			$customer->setCertName('刘红星');
			$customer->setMobileNo('15300001111');
			$customer->setProperties('');
			
			
			// 存证事务
			$token = \App\Lib\Alipay\Notary\NotaryApi::notaryToken( $accountId, $entity, $customer );

			// 位置
			$location = new \App\Lib\Alipay\Notary\Location();
			$location->setIp('192.168.1.123');
			
			// 元数据
			$meta = new \App\Lib\Alipay\Notary\NotaryMeta();
			$meta->setAccountId( $accountId );
			$meta->setToken($token);
			$meta->setPhase('test-1');
			$meta->setTimestamp( date('Y-m-d H:i:s') );
			$meta->setLocation($location);
			
			$str = 'test-1: haha';
			var_dump( '文本内容：'.$str );
			
			// 文本存证
			$txhash = \App\Lib\Alipay\Notary\NotaryApi::textNotary( $str, $meta );
			var_dump( '文本存证：'.$txhash );
			
			// 存证核验
//			$result = \App\Lib\Alipay\Notary\NotaryApi::notaryStatus( $txhash, hash('sha256', $str) , $meta);
//			
//			var_dump( $result );exit;
			
			// 存证下载
			$result = \App\Lib\Alipay\Notary\NotaryApi::textNotaryGet( $txhash, $meta);
			
			var_dump( $result );exit;
			
			// 文件存证
			$result = \App\Lib\Alipay\Notary\NotaryApi::fileNotary( __DIR__.'/test.jpg', $meta );
			
			
		} catch (\Exception $ex) {
			var_dump( $ex );exit;
		}
		
		$info = [
			'refund_no' => '',
			'business_type' => '2',
			'status' => '',
			
			'logistics_id' => '',
			'logistics_name' => '',
			'logistics_no' => '',
		];
		
		
		echo json_encode([
			// 业务
			'business_type' => '业务类型',
			'business_name' => '换货',
			
			// 业务状态
			'state_flow' => [
				[
					'status' => 'A',
					'name' => '申请',
				],
				[
					'status' => 'B',
					'name' => '审核',
				],
				[
					'status' => 'C',
					'name' => '检测',
				],
				[
					'status' => 'D',
					'name' => '完成',
				],
			],
			'status' => 'A',
			'status_text' => '状态提示',
			
			// 订单信息
			'order_info' => [
				'order_no' => '1234567890',
				'datetime' => '2017年03月14日 10:10',
			],
			// 商品信息
			'goods_info' => [
				'goods_name' => '商品名称',
				'goods_img' => 'https://s1.huishoubao.com/zuji/images/content/152248181530767.png',
				'goods_specs' => '64G|亮黑|全网通|12期',
				'zuqi_type' => 'month',
				'zujin' => '499.00',
			],
			
			// 物流表单
			'logistics_form' => [
				'channel_list' => [
					[
						'value' => '1',
						'name' => '顺丰物流',
					],
					[
						'value' => '2',
						'name' => '圆通物流',
					],
				],
				'tips' => '提示：若填写错误，请及时联系客服进行修改',
				'service_tel' => '4000809966',
			],
			// 物流信息
			'logistics_info' => [
				'no' => '1234567890',
				'channel_name' => '顺丰物流',
			],
			
			
			// 换货表单
			'exchange_form' => [
				'reason_list' => [
					[
						'value' => '1',
						'name' => '屏幕问题',
					],
					[
						'value' => '2',
						'name' => '电池问题',
					],
				]
			],
			// 换货信息
			'exchange_info' => [
				'reason_name' => '原因：屏幕问题',
				'reason_text' => '说明“屏幕上有坏点',
			],
			
			// 换货结果
			'exchange_result' => '已经调换并发货，注意签收',
			
			// 退货表单
			'return_form' => [
				'reason_list' => [
					[
						'value' => '1',
						'name' => '屏幕问题',
					],
					[
						'value' => '2',
						'name' => '电池问题',
					],
				]
			],
			// 退款信息
			'return_info' => [
				'reason_name' => '原因：屏幕问题',
				'reason_text' => '说明“屏幕上有坏点',
			],
			
			// 退款结果
			'return_result' => '退款完成',
			
			
			// 检测结果
			'jiance_status' => 'qualified',// qualified：合格；unqualified：不合格
			'jiance_text' => 'Xxxxxxx',
			
			// 支付信息
			'payment_info' => [
				 'status' => 'paid',// paid:已支付；not-paid：未支付
				 'total_amount' => '1000.00',// 总支付金额
				 'discount_amount' => '300.00',// 优惠金额
				 'amount' => '700.00',// 应付金额
				 'amount_list' => [
					 [
						 'name' => '2期租金',
						 'amount' => '700',
					 ],
					 [
						 'name' => '赔偿金',
						 'amount' => '300',
					 ],
				 ],
			],
			
		]);
    }
	/**
	 * 
	 */
	public function testClient(){
		
		$key_arr = [
			'platform_private_key'	=> 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMcMZH43GUYNs1zl8F5kZOHYvPG5P6jSFYKZRbmX3C9ZHbZmrOMGhxDn2JZWu7lfvyjx4Md0ek14GpWPKELjf+m1iEninUh6kUgxMYwyJ2OYfFNZ5W3lbBpZB41AdORjAJrRr1sLbwx1G5Pcdkpu1kQWGti7VT7i7JIeNp7CS3HFAgMBAAECgYEAhBNHAzDQRlmE8Flqu1dmUS2dgc9n3D86IqRNTa7kXU6GlqdehG2qZZ9RacA3Y/OSRjro6a/yD0FocmDBWFDYaDGHkvQjG7n9lnO1nV+R+dMb2s8eCsRL378j9oc+MEeie2N2YCn54GGI4X5jV5oR3zNZLfZcm/IN5ZWS9P1Bh8ECQQDio05lrYHZH5ajSswHwHkWJEy70UwenGuK65yEGelZ8z+cM7XYD/JgPmhUled/KjDu5kIKakahXA0uyiZgQ/FNAkEA4NYM2zK5HpfNl5RBnNwnAkTq00qrWkmT61hvx+bAXfYJtdTq0VR3yyDaJ2Jq4xbGNBh6AzbNvJRG++ymQLfGWQJAUH7qQGjg3qo+iZ7uWq59E2UvL+JFo/WwqLXIcI73d7BS3nrrUmNPlel0it53S45DtQZpXGOk1HjqYb0A5l4bXQJBALhPQCLApfhqQOMtacwIvQGjNU0YPPe6sUOQL7ITe0aLVtJ0RDptn/YobC01BKI8HSa/meXgmy8n7ji+els7S6ECQGO70AuqSFPTO+Tl6iHoguzMBg9SypgvRWb57rMgCXHOCS+nHvEBqu33syB1qEReJB5+75Z7etKGiHssl5dMo68=',
			'platform_public_key'	=> 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDHDGR+NxlGDbNc5fBeZGTh2LzxuT+o0hWCmUW5l9wvWR22ZqzjBocQ59iWVru5X78o8eDHdHpNeBqVjyhC43/ptYhJ4p1IepFIMTGMMidjmHxTWeVt5WwaWQeNQHTkYwCa0a9bC28MdRuT3HZKbtZEFhrYu1U+4uySHjaewktxxQIDAQAB',
			'client_private_key'	=> 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMcMZH43GUYNs1zl8F5kZOHYvPG5P6jSFYKZRbmX3C9ZHbZmrOMGhxDn2JZWu7lfvyjx4Md0ek14GpWPKELjf+m1iEninUh6kUgxMYwyJ2OYfFNZ5W3lbBpZB41AdORjAJrRr1sLbwx1G5Pcdkpu1kQWGti7VT7i7JIeNp7CS3HFAgMBAAECgYEAhBNHAzDQRlmE8Flqu1dmUS2dgc9n3D86IqRNTa7kXU6GlqdehG2qZZ9RacA3Y/OSRjro6a/yD0FocmDBWFDYaDGHkvQjG7n9lnO1nV+R+dMb2s8eCsRL378j9oc+MEeie2N2YCn54GGI4X5jV5oR3zNZLfZcm/IN5ZWS9P1Bh8ECQQDio05lrYHZH5ajSswHwHkWJEy70UwenGuK65yEGelZ8z+cM7XYD/JgPmhUled/KjDu5kIKakahXA0uyiZgQ/FNAkEA4NYM2zK5HpfNl5RBnNwnAkTq00qrWkmT61hvx+bAXfYJtdTq0VR3yyDaJ2Jq4xbGNBh6AzbNvJRG++ymQLfGWQJAUH7qQGjg3qo+iZ7uWq59E2UvL+JFo/WwqLXIcI73d7BS3nrrUmNPlel0it53S45DtQZpXGOk1HjqYb0A5l4bXQJBALhPQCLApfhqQOMtacwIvQGjNU0YPPe6sUOQL7ITe0aLVtJ0RDptn/YobC01BKI8HSa/meXgmy8n7ji+els7S6ECQGO70AuqSFPTO+Tl6iHoguzMBg9SypgvRWb57rMgCXHOCS+nHvEBqu33syB1qEReJB5+75Z7etKGiHssl5dMo68=',
			'client_public_key'		=> 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDHDGR+NxlGDbNc5fBeZGTh2LzxuT+o0hWCmUW5l9wvWR22ZqzjBocQ59iWVru5X78o8eDHdHpNeBqVjyhC43/ptYhJ4p1IepFIMTGMMidjmHxTWeVt5WwaWQeNQHTkYwCa0a9bC28MdRuT3HZKbtZEFhrYu1U+4uySHjaewktxxQIDAQAB',
		];

		$protocol = new \App\Lib\Common\Api\Protocols\RSAClientProtocol();
		// 私钥
		$protocol->setLocalPrivateKey($key_arr['client_private_key']);
		// 客户端公钥
		$protocol->setRemotePublicKey($key_arr['platform_public_key']);
		
		$client = new \App\Lib\Common\Api\ApiClient();
		$client->setProtocel( $protocol );
		
		$context = new \App\Lib\Order\Order\TestApi();
		
		$b = $client->request( $context );
		
		var_dump($context->getResponseData());exit;
	}
		
	/**
	 * 
	 */
	public function testServer(){
		
		$key_arr = [
			'platform_private_key'	=> 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMcMZH43GUYNs1zl8F5kZOHYvPG5P6jSFYKZRbmX3C9ZHbZmrOMGhxDn2JZWu7lfvyjx4Md0ek14GpWPKELjf+m1iEninUh6kUgxMYwyJ2OYfFNZ5W3lbBpZB41AdORjAJrRr1sLbwx1G5Pcdkpu1kQWGti7VT7i7JIeNp7CS3HFAgMBAAECgYEAhBNHAzDQRlmE8Flqu1dmUS2dgc9n3D86IqRNTa7kXU6GlqdehG2qZZ9RacA3Y/OSRjro6a/yD0FocmDBWFDYaDGHkvQjG7n9lnO1nV+R+dMb2s8eCsRL378j9oc+MEeie2N2YCn54GGI4X5jV5oR3zNZLfZcm/IN5ZWS9P1Bh8ECQQDio05lrYHZH5ajSswHwHkWJEy70UwenGuK65yEGelZ8z+cM7XYD/JgPmhUled/KjDu5kIKakahXA0uyiZgQ/FNAkEA4NYM2zK5HpfNl5RBnNwnAkTq00qrWkmT61hvx+bAXfYJtdTq0VR3yyDaJ2Jq4xbGNBh6AzbNvJRG++ymQLfGWQJAUH7qQGjg3qo+iZ7uWq59E2UvL+JFo/WwqLXIcI73d7BS3nrrUmNPlel0it53S45DtQZpXGOk1HjqYb0A5l4bXQJBALhPQCLApfhqQOMtacwIvQGjNU0YPPe6sUOQL7ITe0aLVtJ0RDptn/YobC01BKI8HSa/meXgmy8n7ji+els7S6ECQGO70AuqSFPTO+Tl6iHoguzMBg9SypgvRWb57rMgCXHOCS+nHvEBqu33syB1qEReJB5+75Z7etKGiHssl5dMo68=',
			'platform_public_key'	=> 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDHDGR+NxlGDbNc5fBeZGTh2LzxuT+o0hWCmUW5l9wvWR22ZqzjBocQ59iWVru5X78o8eDHdHpNeBqVjyhC43/ptYhJ4p1IepFIMTGMMidjmHxTWeVt5WwaWQeNQHTkYwCa0a9bC28MdRuT3HZKbtZEFhrYu1U+4uySHjaewktxxQIDAQAB',
			'client_private_key'	=> 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMcMZH43GUYNs1zl8F5kZOHYvPG5P6jSFYKZRbmX3C9ZHbZmrOMGhxDn2JZWu7lfvyjx4Md0ek14GpWPKELjf+m1iEninUh6kUgxMYwyJ2OYfFNZ5W3lbBpZB41AdORjAJrRr1sLbwx1G5Pcdkpu1kQWGti7VT7i7JIeNp7CS3HFAgMBAAECgYEAhBNHAzDQRlmE8Flqu1dmUS2dgc9n3D86IqRNTa7kXU6GlqdehG2qZZ9RacA3Y/OSRjro6a/yD0FocmDBWFDYaDGHkvQjG7n9lnO1nV+R+dMb2s8eCsRL378j9oc+MEeie2N2YCn54GGI4X5jV5oR3zNZLfZcm/IN5ZWS9P1Bh8ECQQDio05lrYHZH5ajSswHwHkWJEy70UwenGuK65yEGelZ8z+cM7XYD/JgPmhUled/KjDu5kIKakahXA0uyiZgQ/FNAkEA4NYM2zK5HpfNl5RBnNwnAkTq00qrWkmT61hvx+bAXfYJtdTq0VR3yyDaJ2Jq4xbGNBh6AzbNvJRG++ymQLfGWQJAUH7qQGjg3qo+iZ7uWq59E2UvL+JFo/WwqLXIcI73d7BS3nrrUmNPlel0it53S45DtQZpXGOk1HjqYb0A5l4bXQJBALhPQCLApfhqQOMtacwIvQGjNU0YPPe6sUOQL7ITe0aLVtJ0RDptn/YobC01BKI8HSa/meXgmy8n7ji+els7S6ECQGO70AuqSFPTO+Tl6iHoguzMBg9SypgvRWb57rMgCXHOCS+nHvEBqu33syB1qEReJB5+75Z7etKGiHssl5dMo68=',
			'client_public_key'		=> 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDHDGR+NxlGDbNc5fBeZGTh2LzxuT+o0hWCmUW5l9wvWR22ZqzjBocQ59iWVru5X78o8eDHdHpNeBqVjyhC43/ptYhJ4p1IepFIMTGMMidjmHxTWeVt5WwaWQeNQHTkYwCa0a9bC28MdRuT3HZKbtZEFhrYu1U+4uySHjaewktxxQIDAQAB',
		];

		$input = file_get_contents("php://input");

		$apiProtocol = new \App\Lib\Common\Api\Protocols\RSAServerProtocol();
		$apiProtocol->setLocalPrivateKey($key_arr['platform_private_key']);
		$apiProtocol->setRemotePublicKey($key_arr['client_public_key']);

		$b = $apiProtocol->setInput($input);
		if( !$b ){
			// 输入不合法
			$output = $apiProtocol->wrap('50000',$apiProtocol->getError());
			echo $output;exit;
		}
		$b = $apiProtocol->unwrap();
		if( !$b ){
			// 输入不合法
			$output = $apiProtocol->wrap('50000',$apiProtocol->getError());
			echo $output;exit;
		}
		//var_dump( $arr );
		$appid = $apiProtocol->getAppid();
		//var_dump( $appid );
		$method = $apiProtocol->getMethod();
		//var_dump( $method );

		// 根据 method 进入不同的 接口处理

		$data = array (
			'id' => '96',
			'content_id' => '0',
			'position_id' => '4',
			'title' => 'sadfsadfsdfa',
			'sub_title' => '',
			'min_price' => '',
			'catid' => '',
			'flag' => 'link',
			'thumb' => 'https://s1.huishoubao.com/zuji/images/content/151850211530582.png',
			'url' => '#',
			'status' => '1',
			'channel_id' => '1',
		  );

		$output = $apiProtocol->wrap('0','','','',$data);

		echo $output;exit;

	}
		
		
	
}
