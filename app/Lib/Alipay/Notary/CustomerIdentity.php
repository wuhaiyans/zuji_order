<?php
/**
 * 蚂蚁金服 金融科技 客户实名身份标识 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */


namespace App\Lib\Alipay\Notary;

/**
 * CustomerIdentity 客户实名身份标识 类
 * <p><b>注意：</b>客户实名身份信息，必须实名，否则无法使用可信存证</p>
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
	 * @return string 身份证号
	 */
	public function getCertType(): string {
		return $this->certType;
	}

	/**
	 * 设置 身份证号
	 * @param string $certNo
	 * @return \App\Lib\Alipay\Bass\CustomerIdentity
	 */
	public function setCertNo(string $certNo): CustomerIdentity {
		$this->certNo = $certNo;
		return $this;
	}
	/**
	 * 读取 身份证号
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


	/**
	 * 转化成数组
	 * @param bool $empty  是否返回空值属性
	 * @return array
	 */
	public function toArray( bool $empty=true ): array{
		$data = [
			'userType'		=> $this->userType,
			'certName'		=> $this->certName,
			'certType'		=> $this->certType,
			'certNo'		=> $this->certNo,
			'mobileNo'		=> $this->mobileNo,
			'properties'	=> $this->properties,
		];
		if( !$empty ){
			foreach($data as $p => $v){
			if( empty($v) ){
					unset($data[$p]);
				continue;
			}
			}
		}
		return $data;
	}
	
	public static function fromArray( array $data ): CustomerIdentity{
		$entity = new CustomerIdentity();
		$entity->userType	= $data['userType'];
		$entity->certName	= $data['certName'];
		$entity->certType	= $data['certType'];
		$entity->certNo		= $data['certNo'];
		$entity->mobileNo	= $data['mobileNo'];
		$entity->properties = $data['properties'];
		return $entity;
	}

}
