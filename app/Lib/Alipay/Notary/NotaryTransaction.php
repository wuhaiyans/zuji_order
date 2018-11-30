<?php

/**
 * 蚂蚁金服 金融科技 可信存证 事务 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Lib\Alipay\Notary;

/**
 * NotaryApp 可信存证 事务
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NotaryTransaction {

	/**
	 * 业务类型
	 * @var int
	 */
	private $business_type;

	/**
	 * 业务数据标识
	 * @var string
	 */
	private $business_no;

	/**
	 * 租户ID
	 * @var string
	 */
	private $accountId;

	/**
	 * 事务凭证
	 * @var string
	 */
	private $token;

	/**
	 * 用户实名身份数据
	 * @var CustomerIdentity
	 */
	private $customer;

	/**
	 * 构造器
	 * @param int $business_type	业务类型
	 * @param string $business_no	业务数据标识
	 */
	public function __construct(int $business_type, string $business_no, string $accountId, string $token, CustomerIdentity $customer) {
		$this->business_type = $business_type;
		$this->business_no = $business_no;
		$this->accountId = $accountId;
		$this->token = $token;
		$this->customer = $customer;
	}

	/**
	 * 获取 业务类型
	 * @return int
	 */
	public function getBusinessType(): int {
		return $this->business_type;
	}

	/**
	 * 获取 业务数据标识
	 * @return string
	 */
	public function getBusinessNo(): string {
		return $this->business_no;
	}

	public function getAccountId(): string {
		return $this->accountId;
	}

	public function getToken(): string {
		return $this->token;
	}

	public function getCustomer(): CustomerIdentity {
		return $this->customer;
	}

	/**
	 * 开启事务
	 * @return bool
	 */
	public function start(): bool {
		return false;
	}

	/**
	 * 文本存证
	 * @param string $content
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta
	 * @return \App\Lib\Alipay\Notary\Notary
	 */
	public function textNotary(string $content, NotaryMeta $meta): Notary {
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		// 文本存证
		$txhash = NotaryApi::textNotary( $content, $meta );
		$contentHash = hash('sha256', $content);
		
		// 保存
		$id = 123;
		
		$textNotary = new Notary($id, $this->token, $txhash, Notary::TYPE_TEXT, $content, $contentHash, $meta);
		
		return $textNotary;
	}
	public function textNotaryGet($txHash):string{
		$meta = new NotaryMeta();
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		return NotaryApi::textNotaryGet( $txHash, $meta );
	}

	/**
	 * 文件存证
	 * @param string $file
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta
	 * @return \App\Lib\Alipay\Notary\Notary
	 */
	public function fileNotary(string $file, NotaryMeta $meta): Notary {
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		// 文件存证
		$txhash = NotaryApi::fileNotary( $file, $meta );
		$content = file_get_contents($file);
		$contentHash = hash('sha256', $content);
		
		// 保存
		$id = 123;
		
		$textNotary = new Notary($id, $this->token, $txhash, Notary::TYPE_TEXT, $file, $contentHash, $meta);
		
		return $textNotary;
	}
	
	/**
	 * 下载 文件存证
	 * @param type $txHash
	 * @return string
	 */
	public function fileNotaryGet($txHash):string{
		$meta = new NotaryMeta();
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		return NotaryApi::fileNotaryGet( $txHash, $meta );
	}

	/**
	 * 核验存证
	 * @param string $txHash	存证凭证
	 * @param string $contentHash	存证内容hash值
	 * @return bool	true：存证；false：不存在
	 */
	public function notaryStatus( string $txHash, string $contentHash ):string{
		$meta = new NotaryMeta();
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		return NotaryApi::notaryStatus( $txHash, $contentHash, $meta);
	}
	
	/**
	 * 下载 事务
	 * @return string
	 */
	public function notaryTransactionGet():string{
		return NotaryApi::notaryTransactionGet( $this->accountId, $this->token );
	}
}
