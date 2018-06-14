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
