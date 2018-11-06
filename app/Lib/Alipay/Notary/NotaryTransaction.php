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
	 * @return bool
	 */
	public function textNotary(string $content): bool {
		return false;
	}

	/**
	 * 文件存证
	 * @param string $file
	 * @return bool
	 */
	public function fileNotary(string $file): bool {
		return false;
	}

}
