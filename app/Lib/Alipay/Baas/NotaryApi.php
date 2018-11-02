<?php
namespace App\Lib\Alipay\Baas;

/**
 * 蚂蚁金服 金融科技 可信存证 API 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

/**
 * 可信存证 API封装
 * <p>注意：存证数据上链成功后，需要等待链上出块完成，数据才可以被查询到。可信存证服
务默认的出块时间是10秒，因此完成数据上链后请至少等待10秒再尝试进内容核验，存证下载接口也同样适。</p>
 * <ul>
 * <li>初始化存证事务</li>
 * <li>文本存证</li>
 * <li>下载文本存证</li>
 * <li>文件存证</li>
 * <li>下载文件存证</li>
 * <li>存证核验</li>
 * <li>下载存证事务</li>
 * </ul>
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NotaryApi {
	
	/**
	 * 商户私钥
	 * @var string
	 */
	private static $priv_key = 'MIID/DCCAuSgAwIBAgIUefihOkZRAUxnHR9ut7cporSIg8IwDQYJKoZIhvcNAQEFBQAwgYExCzAJBgNVBAYTAkNOMTkwNwYDVQQKDDDljJfkuqzlpKnlqIHor5rkv6HnlLXlrZDllYbliqHmnI3liqHmnInpmZDlhazlj7gxHjAcBgNVBAsMFeWPr+S/oei6q+S7vee9kee7nFJTQTEXMBUGA1UEAwwO6JqC6JqB6YeR5pyNQ0EwHhcNMTgxMTAxMTAwMjA1WhcNMjExMDMxMTAwMjA1WjBbMR8wHQYDVQQKFhZBbGlwYXkuY29tIENvcnBvcmF0aW9uMRIwEAYDVQQLDAlDQSBDZW50ZXIxETAPBgNVBAMMCERDT0RNVkNOMREwDwYDVQQFDAhEQ09ETVZDTjCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMw3fPicbisllStZ+rOeF1GPFUpmA+d8lcLXBNGOUENW0VHHyoH72Kvr78RPMsjpAKK45lIE6g7seV+VgferR/FDZWR6xy0OTZYYYLGktiMfKcvdHiI9lqNvQrYQh3UUFZl2bT/7utVqIMkG+v7VtDCoGejUK6tciDX/gwqgy20Qk7v1ckJkPfeoXDhhwIsfbyFOHO8LAwY5mfDjDPjtBX8nSxulBRfLImTngu3DytUGuHt2YnSZb1LSMv3KOyyvtGDjiUMGSHrPvcGFlRBD7Mzp4zwrpySmD7W9K+kOwtabpJp7DsxXmy8UpB2BWI2ISfvmYQypdIIQiBqMbrCHDYsCAwEAAaOBkDCBjTAJBgNVHRMEAjAAMA4GA1UdDwEB/wQEAwIF4DAdBgNVHSUEFjAUBggrBgEFBQcDAgYIKwYBBQUHAwEwEQYJYIZIAYb4QgEBBAQDAgbAMB8GA1UdIwQYMBaAFCHP1ToZY2a5xRK4kKL101uz69taMB0GA1UdDgQWBBTDk6nqqAEgjMyoQFb5VwaYsrPnnTANBgkqhkiG9w0BAQUFAAOCAQEAHOzM5t6yer63gWEapNvknJLrlljg+c7Iuc3s91TFy8QCg1fwoEQrZhudWXkB7P2WUuVX14itxCIof+fWNJIkhCqWvNWOLdVOLLllM6Ci0CBHx4IzBXcuu0xREHJqttHp3z0WOQbK+bU5pHDUDTKMtXQPNv1DbAvg5l8lJH49Dq2GKXCfe0oW9PHZdpBASTQjNdiHyH/poTHcf58iInHh9moEzsd3ZjkNKGCFiXtemwoeGjD5hyTWn2/dt6zK2VMIFwUIOZ3xaBk+aDnu242CNvxhBsCOCBonMeGJxcfxZbkAvM6bgIc5PPh5DqwgGi5aaPTtK+5aMK8yJJdM7g4FZg==';
//	private static $priv_key = 'MIIFDjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQIAykkFyu1iYcCAggAMBQGCCqGSIb3DQMHBAhaTTVF33iBXgSCBMhj4S919rvjna6rL4sujCjQVDqUJeGKrIP8vXdgUJGCdcS2bBYdhU8SjC/jZSGJ9zJkTPUJQMlcZ+xOn7SZdKB+HctUa32RevJ4GNhiV6OBfwcImob416nWYFecMhF4A2ut8y6JSpFkCfDLwvKIR+t1JajpaZT9lZMA81tlL3/1swZsczQCDIuFsgbdPfBmNz0vlC1dIqUXJSjFg0wC9sbPWZj8ovdH1dNXCS/r/jSPzj39ebvq0HKmp9pqncDEKUeuJcMbXRNLq1Xbje0NA0d1grMNrcMV/XErONaeXbxhMfYRioYhoUKvfdRvueikjYfedXWwc5r7rtHdyILC68yy/thYgmKCIyJ0Eg9alXbV1atVcutS6C78VbIO85t2zp4x6eGJRxJTWCPSBEWzlMNeFVq3ly8MT62fR1ssYb6WJRdt+0pp0M3qiLqbS24ofd10OSxbnp3y4rZPQgo1OYai7BXFDVzCB+q3JPEaW4xjkNh+qcrIDbtJmRl5fB0YJStjvu9Rc64OeCoe07LJbcjqsUntyuktlGeIiy2TjrrxmyGBScnN54ez9CyIcMXXG2PYZb4vZbLGUUHOTYb/nMe/Za4zaFNnLMW9LJW1ATg3CBproIpIycXTMJRfh/kMCL7jvLjaeDUgKnHu+Dcnxxvh6mbmug7OVoK1VmEVuNLA34XAtCIhKyHodVhSVKnLDlOJDH1wdAC4WqBaC3iH/wCoNAhP0HpgcLbUIQDiQqWCmyvMdo3z5BqE7sLKPwuFw6hY16i3fru5jNJBfS9WoGU4JVYQP6CGB6tlujjNLZ7rKFiGpqpBukH2gKA1wZFJ5dLd3kNTPpi4MYGe2w0HWZRgcKcXO+6N4MYI9mpYzxgEHBn7hcDJBGTUlC1u2a9dZML90JIYPV2WU/3+JD/j+JQldCsWh+pvju5A1uNRbEZPxESvDtWMFoMYcCuQ9/x+GKRUoN/b/PHzhUmIXpZamdmwO2nsOcn3hJIW7IfUt/C/yQHxeC1HgkG7i+L4pbtTw2Knrxapb8XzaB2FIrKFme5qytgv4AkhpXTiJV6gSKPxkJgQpLRnt0TnjiJAWGA4QTLeBAS9FyD8FLATWMLSOA/VKmhuu2x8Sj9MzxgOW8H0rC3R3pUfZwCmOP1TerdtrTH7ov5mC2fMql0vNe7KsDtbr04tCTolkfm899udgMyDoYcVoNq5oqBBwfglzJLjf5szw5NbpIx3cRTu+bedf25Vm7PIBFM/9b1biiHBaK33JohPSe1NwYAMFqG8FKdnuRg6Or9P7+7MAQsjXzPMLlNSORx3l+/2NUPQwEq46lltzoPrcJD2KtlhD84PbwSicxkYOf6QQKgA7gbjDc/azNjyJdfqP003a+KTT1CPzF/iknVFwGHQ2aEHHhXL89y1OVa834OgPiZkiTt5lwhDqayjFH40dr4K56Sd5Td0C1V8Jro19A6/7DmnNkjXe72y/WDiCj34PayA1GC/cXmVyUkkCVUUhVL0iSR9qa9TSefr/2GHP3aeNilowUvXKha+ysZDhf5OBkwondl66gFWVhxn06BxU2JfU1CxweZRXLDqb5FjQ7QTWsFD5pA6Rj8R4o/5itzR21zSUj4FxbukETG4VBP69cz7xdg=';
//	private static $priv_key = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMcMZH43GUYNs1zl8F5kZOHYvPG5P6jSFYKZRbmX3C9ZHbZmrOMGhxDn2JZWu7lfvyjx4Md0ek14GpWPKELjf+m1iEninUh6kUgxMYwyJ2OYfFNZ5W3lbBpZB41AdORjAJrRr1sLbwx1G5Pcdkpu1kQWGti7VT7i7JIeNp7CS3HFAgMBAAECgYEAhBNHAzDQRlmE8Flqu1dmUS2dgc9n3D86IqRNTa7kXU6GlqdehG2qZZ9RacA3Y/OSRjro6a/yD0FocmDBWFDYaDGHkvQjG7n9lnO1nV+R+dMb2s8eCsRL378j9oc+MEeie2N2YCn54GGI4X5jV5oR3zNZLfZcm/IN5ZWS9P1Bh8ECQQDio05lrYHZH5ajSswHwHkWJEy70UwenGuK65yEGelZ8z+cM7XYD/JgPmhUled/KjDu5kIKakahXA0uyiZgQ/FNAkEA4NYM2zK5HpfNl5RBnNwnAkTq00qrWkmT61hvx+bAXfYJtdTq0VR3yyDaJ2Jq4xbGNBh6AzbNvJRG++ymQLfGWQJAUH7qQGjg3qo+iZ7uWq59E2UvL+JFo/WwqLXIcI73d7BS3nrrUmNPlel0it53S45DtQZpXGOk1HjqYb0A5l4bXQJBALhPQCLApfhqQOMtacwIvQGjNU0YPPe6sUOQL7ITe0aLVtJ0RDptn/YobC01BKI8HSa/meXgmy8n7ji+els7S6ECQGO70AuqSFPTO+Tl6iHoguzMBg9SypgvRWb57rMgCXHOCS+nHvEBqu33syB1qEReJB5+75Z7etKGiHssl5dMo68=';
	/**
	 * 接口地址前缀
	 * @var string
	 */
	private static $url = 'https://cz.tech.antfin.com';
	
	/**
	 * 请求头
	 * @var array
	 */
	private static $header = ['Content-Type: application/json;charset=UTF-8'];
	
	/**
	 * 租户ID
	 * @var string
	 */
	private static $accountId = '123';
	
	/**
	 * 商户的实名信息
	 * @var array
	 */
	private static $entity = [
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
	
	/**
	 * 初始化存证事务
	 * @return string 事务ID
	 * @throws NotaryException 初始化失败抛出异常
	 */
	public static function notaryToken( CustomerIdentity $customer ): string{
		$url = self::$url.'/api/notaryToken';
		$timestamp = self::_getTimestamp();
		$bizId = '2';
		$params = [
			'accountId' => self::$accountId,
			'entity'	=> self::$entity,
			'bizId'		=> $bizId,		// 业务类型；2：合同
			'subBizId'	=> 'LEASING',	// 子业务类型； LEASING：租赁合同
			'customer'	=> [			// 您的客户身份标识
				'userType'	=> $customer->getUserType(),
				'certName'	=> $customer->getCertName(),
				'certType'	=> $customer->getCertType(),
				'certNo'	=> $customer->getCertNo(),
				'mobileNo'	=> $customer->getMobileNo(),
				'properties' => $customer->getProperties(),
			],
			'properties' => '',
			'timestamp' => $timestamp,
			'signedData' => self::_signe(self::$accountId.$bizId.$timestamp),
		];
		$response_str = \App\Lib\Curl::post($url, $params, self::$header);
		
		// 解析返回值
		if( self::_parseResult($response_str, $result)){
			throw new NotaryException( $result );
		}
		
		// 返回 存证事务ID
		if( self::_verifyResult($result) ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
	}
	
	/**
	 * 
	 * @return string
	 * @throws NotaryException
	 */
	public static function textNotary(): string{
		throw new NotaryException();
		return '';
	}
	
	public static function getTextNotary(): string{
		throw new NotaryException();
		return '';
	}
	
	public static function fileNotary(): string{
		throw new NotaryException();
		return '';
	}
	public static function getFileNotary(): string{
		throw new NotaryException();
		return '';
	}
	
	public static function notaryStatus(): string{
		throw new NotaryException();
		return '';
	}
	
	public static function notaryTransaction(): string{
		throw new NotaryException();
		return '';
	}
	
	/**
	 * 解析 API接口返回值
	 * @param mixed $result		入参：API接口返回值
	 * @param mixed $result2		出参：解析结果
	 * @return bool	解析是否成功；true：解析成功；false：解析失败
	 */
	private static function _parseResult( &$result, &$result2 ): bool{
		if( empty($result)){
			return false;
		}
		$result2 = json_decode($result,true);
		if( empty( $result2 ) || !is_array($result2) ){
			return false;
		}
		if( !isset($result2['responseData'])
				|| !isset($result2['success'])
				|| !isset($result2['errMessage'])
				|| !isset($result2['code']) ){
			return false;
		}
		return false;
	}
	
	/**
	 * 校验结果是否成功
	 * @param array $result		解析成功后的API接口返回值
	 * @return bool
	 */
	private static function _verifyResult( array $result ): bool{
		if( $result['success'] ){
			return true;
		}
		return false;
	}
	
	/**
	 * 获取 毫秒时间戳字符串
	 * @return string 毫秒时间戳字符串格式
	 */
	private static function _getTimestamp(): string{
		list($msec, $sec) = explode(' ', microtime());
		return sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	}
	
	/**
	 * 
	 * @param string $data
	 * @return string
	 */
	private static function _signe( string $data ): string{
		$pri_key = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n" .
				wordwrap(self::$priv_key, 64, "\n", true) .
				"\n-----END ENCRYPTED PRIVATE KEY-----";
		$res = openssl_get_privatekey($pri_key);
		var_dump( openssl_error_string() );exit;
		
		openssl_sign($data, $signature, $res, OPENSSL_ALGO_SHA256);
		return $signature;
	}
	
}
