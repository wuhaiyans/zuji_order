<?php
/**
 * 蚂蚁金服 金融科技 客户身份标识 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */


namespace App\Lib\Alipay\Baas;

/**
 * CustomerIdentity 客户身份标识 类
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class CustomerIdentity {
	
	private $userType = 'PERSON';
	private $certType = 'IDENTITY_CARD';
	
	private $certName = '';
	private $certNo = '';
	private $mobileNo = '';
	private $properties = '';
	
	/**
	 * 用户类型
	 * @return string
	 */
	public function getUserType(): string{
		return $this->userType;
	}

	/**
	 * 设置 姓名（用户证件上的）
	 * @param string $certName
	 * @return \App\Lib\Alipay\Bass\CustomerIdentity
	 */
	public function setCertName(string $certName): CustomerIdentity {
		$this->certName = $certName;
		return $this;
	}
	/**
	 * 读取 姓名（用户证件上的）
	 * @return string
	 */
	public function getCertName(): string {
		return $this->certName;
	}

	/**
	 * 读取 证件类型
	 * @return string
	 */
	public function getCertType(): string {
		return $this->certType;
	}

	/**
	 * 设置 证件号
	 * @param string $certNo
	 * @return \App\Lib\Alipay\Bass\CustomerIdentity
	 */
	public function setCertNo(string $certNo): CustomerIdentity {
		$this->certNo = $certNo;
		return $this;
	}
	/**
	 * 读取 证件号
	 * @return string
	 */
	public function getCertNo(): string {
		return $this->certNo;
	}

	/**
	 * 设置 手机号
	 * @param string $mobileNo
	 * @return \App\Lib\Alipay\Bass\CustomerIdentity
	 */
	public function setMobileNo(string $mobileNo): CustomerIdentity {
		$this->mobileNo = $mobileNo;
		return $this;
	}
	/**
	 * 读取 手机号
	 * @return string
	 */
	public function getMobileNo(): string {
		return $this->mobileNo;
	}

	/**
	 * 设置 扩展参数
	 * @param string $properties
	 * @return \App\Lib\Alipay\Bass\CustomerIdentity
	 */
	public function setProperties(string $properties): CustomerIdentity {
		$this->properties = $properties;
		return $this;
	}
	/**
	 * 读取 扩展参数
	 * @return string
	 */
	public function getProperties(): string {
		return $this->properties;
	}


}
