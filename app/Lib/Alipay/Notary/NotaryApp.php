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
 * <ul>
 * <li>第一步：获取存证事务：startTransactionBy*()开启已有事务；registerTransaction()注册新事务</li>
 * <li>第二步：创建存证：createTextNotary()创建文本存证 或 createFileNotary()创建文件存证</li>
 * <li>第三步：存证上传区块链：uploadNotary()</li>
 * </ul>
 * <b>注意：第二步和第三步 必须 在同一个事务中执行（即：执行期间，不可以切换事务）</b>
 * <b>注意：这里的“事务”与数据库中事务的概念不同，只是指一组存证的标识</b>
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
	private $transaction;

	private $handler;

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
		
		$this->handler = new NotaryDataHandler();
	}
	
	/**
	 * 开启 存证事务
	 * 通过数据库查询，初始化存证事务
	 * @param string $order_no		订单编号
	 * @param string $goods_no		商品编号
	 * @return bool
	 */
	public function startTransactionByBusiness(string $order_no, string $goods_no):bool{
		return $this->handler->queryTransactionByBusiness($order_no, $goods_no, $this->transaction);
	}
	/**
	 * 开启 存证事务
	 * 通过数据库查询，初始化存证事务
	 * @param string $order_no		订单编号
	 * @param string $goods_no		商品编号
	 * @return bool
	 */
	public function startTransactionByToken(string $token):bool{
		return $this->handler->queryTransactionByToken($token, $this->transaction);
	}

	/**
	 * 注册 存证事务
	 * @param string $order_no		订单编号
	 * @param string $goods_no		商品编号
	 * @param \App\Lib\Alipay\Notary\CustomerIdentity $customer
	 * @return bool
	 */
	public function registerTransaction(string $order_no, string $goods_no, CustomerIdentity $customer): bool {

		// 业务是否已经注册过存证事务
		// 注册存证事务
		$token = NotaryApi::notaryToken($this->accountId, $this->enterprise, $customer);

		// 创建事务对象
		$this->transaction = new NotaryTransaction(0,$order_no, $goods_no, $this->accountId, $token, $customer);

		// 持久化事务
		$b = $this->handler->createTransaction( $this->transaction );

		return $b;
	}

	
	/**
	 * 获取 事务ID
	 * @return string
	 */
	public function getTransactionToken():string{
		return $this->transaction->getToken();
	}

	/**
	 * 创建 文本存证
	 * @param string $content	文本内容
	 * @param string $phase		阶段值
	 * @return \App\Lib\Alipay\Notary\Notary
	 */
	public function createTextNotary(string $content, string $phase): Notary {
		
		// 内容哈希值
		$contentHash = hash('sha256', $content);
		
		// 存证元数据
		$meta = new NotaryMeta();
		$meta->setPhase( $phase );
		
		// 存证实例
		$notary = new Notary(0, $this->transaction->getId(), $this->transaction->getToken(), $phase, '', Notary::TYPE_TEXT, $content, $contentHash, $meta);
		
		// 存证持久化
		$this->handler->createNotary($notary);
		
		return $notary;
	}

	/**
	 * 下载 文本存证
	 * @param string $txHash	存证凭证
	 * @return string	文本内容
	 */
	public function textNotaryGet(string $txHash): string {
		// 本地存证查询
		//$b = $this->handler->queryNotary(['txHash'=>$txHash], $notary);
		// 接口查询存证内容
		$content = $this->transaction->textNotaryGet($txHash);
		
		return $content;
	}

	/**
	 * 文件存证
	 * @param string $file		文件路径
	 * @param string $phase		阶段值
	 * @return \App\Lib\Alipay\Notary\Notary	存证实例
	 */
	public function createFileNotary(string $file, string $phase): Notary {
		// 读取文件内容
		$content = file_get_contents($file);
		// 内容哈希值
		$contentHash = hash('sha256', $content);
		
		// 存证元数据
		$meta = new NotaryMeta();
		$meta->setPhase( $phase );
		
		// 存证实例
		$notary = new Notary(0, $this->transaction->getId(), $this->transaction->getToken(), '', '', Notary::TYPE_TEXT, $content, $contentHash, $meta);
		
		// 存证持久化
		$this->handler->createNotary($notary);
		
		return $notary;
	}
	/**
	 * 下载 文件存证
	 * @param string $txHash	存证凭证
	 * @return string	文件内容
	 */
	public function fileNotaryGet(string $txHash): string {
		// 本地存证查询
		//$b = $this->handler->queryNotary(['txHash'=>$txHash], $notary);
		// 接口查询存证内容
		$content = $this->transaction->fileNotaryGet($txHash);
		return $content;
	}

	/**
	 * 核验存证
	 * @param string $txHash	存证凭证
	 * @param string $contentHash	存证内容hash值
	 * @return string
	 */
	public function notaryStatus(string $txHash, string $contentHash): string {
		return $this->transaction->notaryStatus($txHash, $contentHash);
	}
	
	/**
	 * 下载 事务
	 * @return string
	 */
	public function notaryTransactionGet():string{
		return $this->transaction->notaryTransactionGet( );
	}

	/**
	 * 查询 存证
	 * @param int $id
	 */
	public function queryNotary( int $id, Notary &$notary=null):bool{
		return $this->handler->queryNotary(['id'=>$id], $notary);
	}
	/**
	 * 
	 * 上传存证
	 * @param \App\Lib\Alipay\Notary\Notary		$notary
	 * @return bool
	 * @throws NotaryException
	 */
	public function uploadNotary(Notary $notary):bool {
		// 存证上链
		$this->transaction->textNotary( $notary );
		// 存在持久化
		$b = $this->handler->uploadNotary($notary);
		return $b;
	}


}
