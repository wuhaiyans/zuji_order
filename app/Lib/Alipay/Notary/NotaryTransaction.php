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
	 * 主键ID
	 * @var int
	 */
	private $id;

	/**
	 * 订单编号
	 * @var string
	 */
	private $order_no;

	/**
	 * 商品编号
	 * @var string
	 */
	private $goods_no;

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
	 * @param int $id
	 * @param string $order_no		订单编号
	 * @param string $goods_no		商品编号
	 * @param string $accountId
	 * @param string $token
	 * @param \App\Lib\Alipay\Notary\CustomerIdentity $customer
	 */
	public function __construct(int $id, string $order_no, string $goods_no, string $accountId, string $token, CustomerIdentity $customer) {
		$this->id = $id;
		$this->order_no = $order_no;
		$this->goods_no = $goods_no;
		$this->accountId = $accountId;
		$this->token = $token;
		$this->customer = $customer;
	}

	/**
	 * 获取主键ID
	 * @param int $id
	 * @return \App\Lib\Alipay\Notary\NotaryTransaction
	 */
	public function setId(int $id):NotaryTransaction{
		$this->id = $id;
		return $this;
	}
	/**
	 * 获取主键ID
	 * @return int
	 */
	public function getId():int{
		return $this->id;
	}
	
	/**
	 * 获取 订单编号
	 * @return int
	 */
	public function getOrderNo(): string {
		return $this->order_no;
	}

	/**
	 * 获取 商品编号
	 * @return string
	 */
	public function getGoodsNo(): string {
		return $this->goods_no;
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
	 * @param \App\Lib\Alipay\Notary\Notary		$notary
	 * @return 
	 * @throws NotaryException
	 */
	public function textNotary( Notary $notary) {
		$meta = $notary->getMeta();
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		$meta->setTimestamp( date('Y-m-d H:i:s') );
		
		// 文本存证（上传文本哈希具有相同效应）
		$txHash = NotaryApi::textNotary( $notary->getContentHash(), $meta );
		$notary->setTxHash($txHash);
	}
	public function textNotaryGet($txHash):string{
		$meta = new NotaryMeta();
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		return NotaryApi::textNotaryGet( $txHash, $meta );
	}

	/**
	 * 文件存证
	 * @param \App\Lib\Alipay\Notary\Notary		$notary
	 * @return 
	 */
	public function fileNotary(Notary $notary) {
		$meta = $notary->getMeta();
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		$meta->setTimestamp( date('Y-m-d H:i:s') );
		
		// 文件存证
		$txHash = NotaryApi::fileNotary( $notary->getContent(), $meta );
		
		$notary->setTxHash($txHash);
		
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
	 * @return string
	 */
	public function notaryStatus( string $txHash, string $contentHash ):string{
		$meta = new NotaryMeta();
		$meta->setAccountId($this->accountId);
		$meta->setToken($this->token);
		
		$contentHash = hash('sha256',$contentHash);
		
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
