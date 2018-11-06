<?php

/**
 * 蚂蚁金服 金融科技 可信存证 存证 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Lib\Alipay\Notary;

/**
 * Notary 存证 类
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Notary {

	const TYPE_TEXT = 1;
	const TYPE_FILE = 2;

	/**
	 * 数据ID
	 * @var int
	 */
	private $id;

	/**
	 * 事务凭证
	 * @var string
	 */
	private $transactionToken;

	/**
	 * 存证凭证
	 * @var string
	 */
	private $txHash;

	/**
	 * 元数据
	 * @var NotaryMeta
	 */
	private $meta;

	/**
	 * 存证类型； 1：文本存证；2：文件存证
	 * @var int
	 */
	private $type;

	/**
	 * 存证内容：
	 * type=1时，保存文本内容
	 * type=2时，保存文件地址
	 * @var string
	 */
	private $content;

	/**
	 * 存证内容Hash：content的 sha256 Hash值
	 * @var string
	 */
	private $contentHash;

	/**
	 * 构造器
	 * @param int $id
	 * @param string $transactionToken
	 * @param string $txHash
	 * @param string $type
	 * @param string $content
	 * @param string $contentHash
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta
	 */
	public function __construct(int $id, string $transactionToken, string $txHash, int $type, string $content, string $contentHash, NotaryMeta $meta) {
		$this->id = $id;
		$this->transactionToken = $transactionToken;
		$this->txHash = $txHash;
		$this->type = $type;
		$this->content = $content;
		$this->contentHash = $contentHash;
		$this->meta = $meta;
	}

	/**
	 * 读取 数据ID
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * 读取 事务凭证
	 * @return string
	 */
	public function getTransactionToken(): string {
		return $this->transactionToken;
	}

	/**
	 * 读取 存证凭证
	 * @return string
	 */
	public function getTxHash(): string {
		return $this->txHash;
	}

	/**
	 * 读取 存证元数据
	 * @return \App\Lib\Alipay\Notary\NotaryMeta
	 */
	public function getMeta(): NotaryMeta {
		return $this->meta;
	}

	/**
	 * 读取 存证类型
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * 读取 存证内容
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}

	/**
	 * 读取 存证内容Hash值
	 * @return string
	 */
	public function getContentHash(): string {
		return $this->contentHash;
	}

	/**
	 * 设置 数据ID
	 * @param int $id
	 * @return $this
	 */
	public function setId(int $id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * 设置 事务凭证
	 * @param string $transactionToken
	 * @return $this
	 */
	public function setTransactionToken(string $transactionToken) {
		$this->transactionToken = $transactionToken;
		return $this;
	}

	/**
	 * 设置 存证凭证
	 * @param string $txHash
	 * @return $this
	 */
	public function setTxHash(string $txHash) {
		$this->txHash = $txHash;
		return $this;
	}

	/**
	 * 设置 存证元数据
	 * @param \App\Lib\Alipay\Notary\NotaryMeta $meta
	 * @return $this
	 */
	public function setMeta(NotaryMeta $meta) {
		$this->meta = $meta;
		return $this;
	}

	/**
	 * 设置 存证类型
	 * @param int $type
	 * @return $this
	 */
	public function setType(int $type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * 设置 存证内容
	 * @param string $content
	 * @return $this
	 */
	public function setContent(string $content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * 设置 存证内容Hash值
	 * @param string $contentHash
	 * @return $this
	 */
	public function setContentHash(string $contentHash) {
		$this->contentHash = $contentHash;
		return $this;
	}

}
