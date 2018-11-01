<?php
namespace App\Lib\Alipay\Bass;

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
	 *
	 * @var 商户私钥
	 */
	private $priv_key = '';
	
	private $url = 'https://cz.tech.antfin.com';
	private $header = ['Content-Type: application/json;charset=UTF-8'];
	
	// 租户ID
	private $accountId = '123';
	
	// 商户的实名信息
	private $entity = [
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
	public function notaryToken( CustomerIdentity $customer ): string{
		$url = $this->url.'/api/notaryToken';
		$timestamp = $this->_getTimestamp();
		$bizId = '2';
		$params = [
			'accountId' => $this->accountId,
			'entity'	=> $this->entity,
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
			'signedData' => $this->_signe($this->accountId.$bizId.$timestamp),
		];
		$response_str = \App\Lib\Curl::post($url, $params, $this->header);
		
		// 解析返回值
		if( $this->_parseResult($response_str, $result)){
			throw new NotaryException( $result );
		}
		
		// 返回 存证事务ID
		if( $this->_verifyResult($result) ){
			 return $result['responseData'];
		}
		
		throw new NotaryException( $result );
	}
	
	/**
	 * 
	 * @return string
	 * @throws NotaryException
	 */
	public function textNotary(): string{
		throw new NotaryException();
		return '';
	}
	
	public function getTextNotary(): string{
		throw new NotaryException();
		return '';
	}
	
	public function fileNotary(): string{
		throw new NotaryException();
		return '';
	}
	public function getFileNotary(): string{
		throw new NotaryException();
		return '';
	}
	
	public function notaryStatus(): string{
		throw new NotaryException();
		return '';
	}
	
	public function notaryTransaction(): string{
		throw new NotaryException();
		return '';
	}
	
	/**
	 * 解析 API接口返回值
	 * @param mixed $result		入参：API接口返回值
	 * @param mixed $result2		出参：解析结果
	 * @return bool	解析是否成功；true：解析成功；false：解析失败
	 */
	private function _parseResult( &$result, &$result2 ): bool{
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
	private function _verifyResult( array $result ): bool{
		if( $result['success'] ){
			return true;
		}
		return false;
	}
	
	/**
	 * 获取 毫秒时间戳字符串
	 * @return string 毫秒时间戳字符串格式
	 */
	private function _getTimestamp(): string{
		list($msec, $sec) = explode(' ', microtime());
		return sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	}
	
	/**
	 * 
	 * @param string $data
	 * @return string
	 */
	private function _signe( string $data ): string{
		
		$priv_key_id = "-----BEGIN RSA PRIVATE KEY-----\n" .
				wordwrap($this->priv_key, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";
		openssl_sign($data, $signature, $priv_key_id, OPENSSL_ALGO_SHA256);
		return $signature;
	}
	
}
