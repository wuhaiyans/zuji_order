<?php
namespace App\Lib\Alipay\Notary;

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
//	private static $priv_key = 'MIID/DCCAuSgAwIBAgIUefihOkZRAUxnHR9ut7cporSIg8IwDQYJKoZIhvcNAQEFBQAwgYExCzAJBgNVBAYTAkNOMTkwNwYDVQQKDDDljJfkuqzlpKnlqIHor5rkv6HnlLXlrZDllYbliqHmnI3liqHmnInpmZDlhazlj7gxHjAcBgNVBAsMFeWPr+S/oei6q+S7vee9kee7nFJTQTEXMBUGA1UEAwwO6JqC6JqB6YeR5pyNQ0EwHhcNMTgxMTAxMTAwMjA1WhcNMjExMDMxMTAwMjA1WjBbMR8wHQYDVQQKFhZBbGlwYXkuY29tIENvcnBvcmF0aW9uMRIwEAYDVQQLDAlDQSBDZW50ZXIxETAPBgNVBAMMCERDT0RNVkNOMREwDwYDVQQFDAhEQ09ETVZDTjCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMw3fPicbisllStZ+rOeF1GPFUpmA+d8lcLXBNGOUENW0VHHyoH72Kvr78RPMsjpAKK45lIE6g7seV+VgferR/FDZWR6xy0OTZYYYLGktiMfKcvdHiI9lqNvQrYQh3UUFZl2bT/7utVqIMkG+v7VtDCoGejUK6tciDX/gwqgy20Qk7v1ckJkPfeoXDhhwIsfbyFOHO8LAwY5mfDjDPjtBX8nSxulBRfLImTngu3DytUGuHt2YnSZb1LSMv3KOyyvtGDjiUMGSHrPvcGFlRBD7Mzp4zwrpySmD7W9K+kOwtabpJp7DsxXmy8UpB2BWI2ISfvmYQypdIIQiBqMbrCHDYsCAwEAAaOBkDCBjTAJBgNVHRMEAjAAMA4GA1UdDwEB/wQEAwIF4DAdBgNVHSUEFjAUBggrBgEFBQcDAgYIKwYBBQUHAwEwEQYJYIZIAYb4QgEBBAQDAgbAMB8GA1UdIwQYMBaAFCHP1ToZY2a5xRK4kKL101uz69taMB0GA1UdDgQWBBTDk6nqqAEgjMyoQFb5VwaYsrPnnTANBgkqhkiG9w0BAQUFAAOCAQEAHOzM5t6yer63gWEapNvknJLrlljg+c7Iuc3s91TFy8QCg1fwoEQrZhudWXkB7P2WUuVX14itxCIof+fWNJIkhCqWvNWOLdVOLLllM6Ci0CBHx4IzBXcuu0xREHJqttHp3z0WOQbK+bU5pHDUDTKMtXQPNv1DbAvg5l8lJH49Dq2GKXCfe0oW9PHZdpBASTQjNdiHyH/poTHcf58iInHh9moEzsd3ZjkNKGCFiXtemwoeGjD5hyTWn2/dt6zK2VMIFwUIOZ3xaBk+aDnu242CNvxhBsCOCBonMeGJxcfxZbkAvM6bgIc5PPh5DqwgGi5aaPTtK+5aMK8yJJdM7g4FZg==';
	private static $priv_key = 'MIIFDjBABgkqhkiG9w0BBQ0wMzAbBgkqhkiG9w0BBQwwDgQIAykkFyu1iYcCAggAMBQGCCqGSIb3DQMHBAhaTTVF33iBXgSCBMhj4S919rvjna6rL4sujCjQVDqUJeGKrIP8vXdgUJGCdcS2bBYdhU8SjC/jZSGJ9zJkTPUJQMlcZ+xOn7SZdKB+HctUa32RevJ4GNhiV6OBfwcImob416nWYFecMhF4A2ut8y6JSpFkCfDLwvKIR+t1JajpaZT9lZMA81tlL3/1swZsczQCDIuFsgbdPfBmNz0vlC1dIqUXJSjFg0wC9sbPWZj8ovdH1dNXCS/r/jSPzj39ebvq0HKmp9pqncDEKUeuJcMbXRNLq1Xbje0NA0d1grMNrcMV/XErONaeXbxhMfYRioYhoUKvfdRvueikjYfedXWwc5r7rtHdyILC68yy/thYgmKCIyJ0Eg9alXbV1atVcutS6C78VbIO85t2zp4x6eGJRxJTWCPSBEWzlMNeFVq3ly8MT62fR1ssYb6WJRdt+0pp0M3qiLqbS24ofd10OSxbnp3y4rZPQgo1OYai7BXFDVzCB+q3JPEaW4xjkNh+qcrIDbtJmRl5fB0YJStjvu9Rc64OeCoe07LJbcjqsUntyuktlGeIiy2TjrrxmyGBScnN54ez9CyIcMXXG2PYZb4vZbLGUUHOTYb/nMe/Za4zaFNnLMW9LJW1ATg3CBproIpIycXTMJRfh/kMCL7jvLjaeDUgKnHu+Dcnxxvh6mbmug7OVoK1VmEVuNLA34XAtCIhKyHodVhSVKnLDlOJDH1wdAC4WqBaC3iH/wCoNAhP0HpgcLbUIQDiQqWCmyvMdo3z5BqE7sLKPwuFw6hY16i3fru5jNJBfS9WoGU4JVYQP6CGB6tlujjNLZ7rKFiGpqpBukH2gKA1wZFJ5dLd3kNTPpi4MYGe2w0HWZRgcKcXO+6N4MYI9mpYzxgEHBn7hcDJBGTUlC1u2a9dZML90JIYPV2WU/3+JD/j+JQldCsWh+pvju5A1uNRbEZPxESvDtWMFoMYcCuQ9/x+GKRUoN/b/PHzhUmIXpZamdmwO2nsOcn3hJIW7IfUt/C/yQHxeC1HgkG7i+L4pbtTw2Knrxapb8XzaB2FIrKFme5qytgv4AkhpXTiJV6gSKPxkJgQpLRnt0TnjiJAWGA4QTLeBAS9FyD8FLATWMLSOA/VKmhuu2x8Sj9MzxgOW8H0rC3R3pUfZwCmOP1TerdtrTH7ov5mC2fMql0vNe7KsDtbr04tCTolkfm899udgMyDoYcVoNq5oqBBwfglzJLjf5szw5NbpIx3cRTu+bedf25Vm7PIBFM/9b1biiHBaK33JohPSe1NwYAMFqG8FKdnuRg6Or9P7+7MAQsjXzPMLlNSORx3l+/2NUPQwEq46lltzoPrcJD2KtlhD84PbwSicxkYOf6QQKgA7gbjDc/azNjyJdfqP003a+KTT1CPzF/iknVFwGHQ2aEHHhXL89y1OVa834OgPiZkiTt5lwhDqayjFH40dr4K56Sd5Td0C1V8Jro19A6/7DmnNkjXe72y/WDiCj34PayA1GC/cXmVyUkkCVUUhVL0iSR9qa9TSefr/2GHP3aeNilowUvXKha+ysZDhf5OBkwondl66gFWVhxn06BxU2JfU1CxweZRXLDqb5FjQ7QTWsFD5pA6Rj8R4o/5itzR21zSUj4FxbukETG4VBP69cz7xdg=';
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
	 * 初始化存证事务
	 * 注意：该接口是“application/json”方式提交
	 * 提交值整体 json_encode() 编码
	 * @param string	$accountId		租户ID
	 * @param array		$entity			商户实名信息
	 * @param \App\Lib\Alipay\Notary\CustomerIdentity $customer		存证元数据
	 * @return string 事务ID
	 * @throws NotaryException 初始化失败抛出异常
	 */
	public static function notaryToken(string $accountId, EnterpriseIdentity $entity, CustomerIdentity $customer ): string{
		$url = self::$url.'/api/notaryToken';
		$timestamp = self::_getTimestamp();
		$bizId = '3'; //业务分类 3: 租赁； subBizId 留空 2018-12-07 邹雪晴（阿里巴巴）要求修改的
		$params = [
			'accountId' => $accountId,
			'entity'	=> $entity->toArray(),
			'bizId'		=> $bizId,		// 业务类型；
		//	'subBizId'	=> 'LEASING',			// 子业务类型；
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
			'signedData' => self::_signe($accountId.$bizId.$timestamp),
		];
		$params = json_encode($params);
		$response_str = \App\Lib\Curl::post($url, $params, self::$header);
		// 解析返回值
		if( !self::_parseResult($response_str, $result)){
			throw new NotaryException( $result );
		}
		
		// 返回 存证事务ID
		if( self::_verifyResult($result) ){
			 return $result['responseData'];
		}
		throw new NotaryException( $result );
	}
	
	/**
	 * 文本存证
	 * 注意：该接口是“multipart/form-data”方式提交
	 * meta的值做 json_encode()编码
	 * @param string $content		存证文本内容
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta		存证元数据
	 * @return string
	 * @throws NotaryException
	 */
	public static function textNotary(string $content, NotaryMeta $meta): string{
		$url = self::$url.'/api/textNotary';
		$timestamp = self::_getTimestamp();
		
		$params = [
			'meta' => json_encode($meta->toArray()),
			'notaryContent' => $content,
			'timestamp' => $timestamp,
			'signedData' => self::_signe($meta->getAccountId().$meta->getPhase().$timestamp),
		];
		$response_str = \App\Lib\Curl::post($url, $params);
		// 解析返回值
		if( !self::_parseResult($response_str, $result)){
			throw new NotaryException( $result );
		}
		
		// 返回 存证事务ID
		if( self::_verifyResult($result) && $result['code'] == 'ACCEPTED' ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
	}
	
	/**
	 * 下载文本存证内容
	 * 注意：该接口是“application/json”方式提交
	 * 提交值整体 json_encode() 编码
	 * @param string $txHash 文本存证凭证
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta	元数据
	 * @return string  文本存证内容
	 * @throws NotaryException
	 */
	public static function textNotaryGet(string $txHash, NotaryMeta $meta): string{
		$url = self::$url.'/api/textNotaryGet';
		$timestamp = self::_getTimestamp();
		
		$params = json_encode([
			'txHash' => $txHash,
			// 注意：meta 只包含 accountId，否则会差找不到存证（阿里技术反馈）
			'meta' => [
				'accountId' => $meta->getAccountId(),
			],
			'timestamp' => $timestamp,
			'signedData' => self::_signe($meta->getAccountId().$txHash.$timestamp),
		]);
		$response_str = \App\Lib\Curl::post($url, $params,['Content-Type: application/json;charset=UTF-8']);
		// 解析返回值
		if( !self::_parseResult($response_str, $result)){
			throw new NotaryException( $result );
		}
		
		// 返回 存证是否存在
		if( self::_verifyResult($result) ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
	}
	
	/**
	 * 文件存证
	 * @param string $file		文件地址
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta		存证元数据
	 * @return string
	 * @throws NotaryException
	 */
	public static function fileNotary(string $file, NotaryMeta $meta): string{
		$url = self::$url.'/api/fileNotary';
		$timestamp = self::_getTimestamp();
		
		$params = [
			'meta' => json_encode($meta->toArray()),
			'notaryFile' => new \CURLFile(realpath($file)),
			'timestamp' => $timestamp,
			'signedData' => self::_signe($meta->getAccountId().$meta->getPhase().$timestamp),
		];
		$response_str = \App\Lib\Curl::post($url, $params);
		// 解析返回值
		if( !self::_parseResult($response_str, $result)){
			throw new NotaryException( $result );
		}
		
		// 返回 存证事务ID
		if( self::_verifyResult($result) && $result['code'] == 'ACCEPTED' ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
	}
	
	/**
	 * 下载 文件存证内容
	 * 注意：该接口是“application/json”方式提交
	 * 提交值整体 json_encode() 编码
	 * @param string $txHash 文件存证凭证
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta	元数据
	 * @return string  文件存证内容
	 * @throws NotaryException
	 */
	public static function fileNotaryGet(string $txHash, NotaryMeta $meta): string{
		$url = self::$url.'/api/fileNotaryGet';
		$timestamp = self::_getTimestamp();
		
		$params = json_encode([
			'txHash' => $txHash,
			// 注意：meta 只包含 accountId，否则会差找不到存证（阿里技术反馈）
			'meta' => [
				'accountId' => $meta->getAccountId(),
			],
			'timestamp' => $timestamp,
			'signedData' => self::_signe($meta->getAccountId().$txHash.$timestamp),
		]);
		// 下载 文件存证，正确时，直接输出文件内容
		$response_str = \App\Lib\Curl::post($url, $params,['Content-Type: application/json;charset=UTF-8']);
		
		// 解析返回值
		if( !self::_parseResult($response_str, $result)){
			// 解析失败，可以确定 返回值结果已经不是通用格式了
			// 如果长度>0，则认为是文件内容
			if( strlen($response_str) ){
				return $response_str;
			}
			throw new NotaryException( $result );
		}
		
		// 返回 存证是否存在
		if( self::_verifyResult($result) && $result['code'] == 'OK' ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
	}
	
	/**
	 * 
	 * 存证事务核验
	 * 注意：该接口是“application/json”方式提交
	 * 提交值整体 json_encode() 编码
	 * @param string $txHash	存证凭证
	 * @param string $contentHash	存证内容或存证文件的sha256哈希值
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta 元数据，accountId必选，其余字段可选
	 * @return string
	 * @throws NotaryException
	 */
	public static function notaryStatus(string $txHash,string $contentHash, NotaryMeta $meta): string{
		$url = self::$url.'/api/notaryStatus';
		$timestamp = self::_getTimestamp();
		
		$params = json_encode([
			'txHash' => $txHash,
			'contentHash' => $contentHash,
			// 注意：meta 只包含 accountId，否则会差找不到存证（阿里技术反馈）
			'meta' => [
				'accountId' => $meta->getAccountId(),
			],
			'timestamp' => $timestamp,
			'signedData' => self::_signe($meta->getAccountId().$txHash.$timestamp),
		]);
		$response_str = \App\Lib\Curl::post($url, $params,['Content-Type: application/json;charset=UTF-8']);
		// 解析返回值
		if( !self::_parseResult($response_str, $result) ){
			throw new NotaryException( $result );
		}
		
		// 返回 存证是否存在
		if( self::_verifyResult($result) && $result['code'] == 'OK' ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
	}
	
	/**
	 * 下载事务
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta
	 * @return string
	 * @throws NotaryException
	 */
	public static function notaryTransactionGet($accountId, $token): string{
		$url = self::$url.'/api/notaryTransactionGet';
		$timestamp = self::_getTimestamp();
		
		$params = json_encode([
			'token' => $token,
			'accountId' => $accountId,
			'timestamp' => $timestamp,
			'signedData' => self::_signe($accountId.$token.$timestamp),
		]);
		$response_str = \App\Lib\Curl::post($url, $params,['Content-Type: application/json;charset=UTF-8']);
		
		// 解析返回值
		if( !self::_parseResult($response_str, $result)){
			// 解析失败，可以确定 返回值结果已经不是通用格式了
			// 如果长度>0，则认为是事务内容
			if( strlen($response_str) ){
				return $response_str;
			}
			throw new NotaryException( $result );
		}
		
		// 返回 存证是否存在
		if( self::_verifyResult($result) && $result['code'] == 'OK' ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
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
		return true;
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
		$pri_key = '-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAMdoMcJwN584qMj/
PRbEd+2so6+sz/mXGJ3gVv5hkosnB5hG8YhnF8mao71G4pK4XqDpX52YzTtICAGI
Q3FcoKIbSbR/7Jfd2jATIMZkeZ6erUx0RrXUhZcQ8cNOjL+W0eFfhpcfp/UbqKfv
dvLK9aRJOCk2+pJxnz5etj265zSRAgMBAAECgYEAt6+Bds0MT71PraeAzII6v1Oy
jNcx1YacBIJtYHLdHRXc5yciwzXEMdAjWO39NI5ljPCtRW1GUH9v8IlnJvvecxVN
v/oQ1YKxWP2Wn8WM0qm/nVGnKjBVYGO40rGYScqs9RQcGm/SUpEiJLXwqPTXkE0F
NSEum4Zh5FkTkWN/luECQQDkAxZeouqt2M8GHTdM1TkyxYVRmXyiQ8VCkIfoYzo7
SJy3CSR6P53OmVJxBp23UUKuXUH9M2jFBa+hnPvxAnXtAkEA3+I9PlT3cOPf+4Tn
6dm9sI7kYZQT7J8F5odV5okSI32ID5bFFy+XwC2uBMFuwmiX90IOsdyvRZD0Z7qb
T1aktQJAWD6ppa6/zNCgLumXwXC0VmYDlvUkO1inO3/cWaAtpUwQ+vXa3EVKue60
7XF2EMCuYfVN2MTQw4/TzWSITVp6cQJAbevVZ/Itnway5PnnJ6DZioNNzD743Vdi
fUH7QfoQps4ubID4+Z5LYnbLFtil+duCqUqMjnUstPorlXZAZN7EdQJAVzA/a5sN
ujuP5+7901+MGl2AhJ+Lkyylo4UgX+PK+sWhJMg1a1u9hQfPJMG3OwEiSwIObBuq
pt5rWqJOmo6+Ew==
-----END PRIVATE KEY-----';
		$b = openssl_sign($data, $signature, $pri_key, OPENSSL_ALGO_SHA256);
		$signature = bin2hex($signature);
		return $signature;
	}
	
}
