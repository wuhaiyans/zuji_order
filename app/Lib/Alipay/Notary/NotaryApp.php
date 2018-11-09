<?php

/**
 * 蚂蚁金服 金融科技 可信存证 应用 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Lib\Alipay\Notary;

/**
 * NotaryApp 可信存证 应用
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NotaryApp {

	/**
	 * 租户ID
	 * @var string
	 */
	private $accountId;

	/**
	 * 企业实名身份信息
	 * @var EnterpriseIdentity 
	 */
	private $enterprise;

	/**
	 * 存证事务
	 * @var NotaryTransaction
	 */
	private $transation;

	/**
	 * 构造函数
	 * @param string $accountId	业务类型
	 */
	public function __construct(string $accountId) {
		$this->accountId = $accountId;
		// 企业实名身份信息
		$this->enterprise = new EnterpriseIdentity();
		$this->enterprise->setCertName('深圳回收宝科技有限公司');
		$this->enterprise->setCertType($this->enterprise::CERT_TYPE_UNIFIED_SOCIAL_CREDIT_CODE);
		$this->enterprise->setCertNo('91440300311802545U');
		$this->enterprise->setLegalPerson('何帆');
		$this->enterprise->setLegalPersonId('420102198108011012');
		$this->enterprise->setAgent('赵明亮');
		$this->enterprise->setAgentId('232301199005211535');
	}

	/**
	 * 注册 存证事务
	 * @param int $business_type
	 * @param string $business_no
	 * @param \App\Lib\Alipay\Notary\CustomerIdentity $customer
	 * @return bool
	 */
	public function registerTransation(int $business_type, string $business_no, CustomerIdentity $customer): bool {

		// 业务是否已经注册过存证事务
		// 注册存证事务
		$token = NotaryApi::notaryToken($this->accountId, $this->enterprise, $customer);

		// 保存本地

		$this->transation = new NotaryTransaction($business_type, $business_no, $this->accountId, $token, $customer);

		return true;
	}

	/**
	 * 开启 存证事务
	 * @param int $business_type	业务类型
	 * @param string $business_no	业务数据标识
	 */
	public function startTransation(int $business_type, string $business_no): bool {
		if ($this->transation) {
			return true;
		}

		//
		$token = '123';
		$customer = new CustomerIdentity();
		$customer->setCertNo('130423198906021038');
		$customer->setCertName('刘红星');
		$customer->setMobileNo('15300001111');

		$this->transation = new NotaryTransaction($business_type, $business_no, $token, $customer);
		return true;
	}
	
	public function getTransationToken():string{
		return $this->transation->getToken();
	}

	/**
	 * 文本存证
	 * @param string $content
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta
	 * @return \App\Lib\Alipay\Notary\Notary
	 */
	public function textNotary(string $content, NotaryMeta $meta): Notary {
		return $this->transation->textNotary($content, $meta);
	}

	/**
	 * 下载 文本存证
	 * @param string $txHash
	 * @return string
	 */
	public function textNotaryGet(string $txHash): string {
		return $this->transation->textNotaryGet($txHash);
	}

	/**
	 * 文件存证
	 * @param string $file
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta
	 * @return \App\Lib\Alipay\Notary\Notary
	 */
	public function fileNotary(string $file, NotaryMeta $meta): Notary {
		return $this->transation->fileNotary($file, $meta);
	}
	/**
	 * 下载 文件存证
	 * @param string $txHash
	 * @return string
	 */
	public function fileNotaryGet(string $txHash): string {
		return $this->transation->fileNotaryGet($txHash);
	}

	/**
	 * 核验存证
	 * @param string $txHash	存证凭证
	 * @param string $contentHash	存证内容hash值
	 * @return bool	true：存证；false：不存在
	 */
	public function notaryStatus(string $txHash, string $contentHash): string {
		return $this->transation->notaryStatus($txHash, $contentHash);
	}
	
	/**
	 * 下载 事务
	 * @return string
	 */
	public function notaryTransactionGet():string{
		return $this->transation->notaryTransactionGet( );
	}

}
