<?php
/**
 * 蚂蚁金服 金融科技 商户实名身份标识 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */


namespace App\Lib\Alipay\Notary;

/**
 * EnterpriseIdentity 商户实名身份标识 类
 * <p><b>注意：</b>商户实名身份信息，必须实名，否则无法使用可信存证</p>
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class EnterpriseIdentity {
	
	/**
	 * 统一社会信用代码
	 * @var string 统一社会信用代码
	 */
	const CERT_TYPE_UNIFIED_SOCIAL_CREDIT_CODE = 'UNIFIED_SOCIAL_CREDIT_CODE';
	/**
	 * 企业工商注册号
	 * @var string 企业工商注册号
	 */
	const CERT_TYPE_ENTERPRISE_REGISTERED_NUMBER = 'ENTERPRISE_REGISTERED_NUMBER';

	private $userType = 'ENTERPRISE';
	private $certType = 'UNIFIED_SOCIAL_CREDIT_CODE';
	
	private $certName = '深圳回收宝科技有限公司';
	private $certNo = '91440300311802545U';
	private $mobileNo = '';
	private $legalPerson = '何帆';
	private $legalPersonId = '420102198108011012';
	private $agent = '赵明亮';
	private $agentId = '232301199005211535';
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
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setCertName(string $certName): EnterpriseIdentity {
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
	public function setCertType(string $certType): EnterpriseIdentity {
		$this->certType = $certType;
		return $this;
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
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setCertNo(string $certNo): EnterpriseIdentity {
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
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setMobileNo(string $mobileNo): EnterpriseIdentity {
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
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setProperties(string $properties): EnterpriseIdentity {
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
	 * 
	 * @param string $legalPerson
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setLegalPerson(string $legalPerson): EnterpriseIdentity {
		$this->legalPerson = $legalPerson;
		return $this;
	}
	/**
	 * 
	 * @return string
	 */
	public function getLegalPerson():string {
		return $this->legalPerson;
	}

	/**
	 * 设置 企业法人身份证号
	 * @param string $legalPersonId
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setLegalPersonId(string $legalPersonId): EnterpriseIdentity {
		$this->legalPersonId = $legalPersonId;
		return $this;
	}
	/**
	 * 获取 企业法人身份证号
	 * @return string
	 */
	public function getLegalPersonId():string {
		return $this->legalPersonId;
	}

	/**
	 * 设置 经办人姓名
	 * @param string $agent
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setAgent(string $agent): EnterpriseIdentity {
		$this->agent = $agent;
		return $this;
	}
	/**
	 * 获取 经办人姓名
	 * @return string
	 */
	public function getAgent():string {
		return $this->agent;
	}

	/**
	 * 设置 经办人身份证
	 * @param string $agentId
	 * @return \App\Lib\Alipay\Notary\EnterpriseIdentity
	 */
	public function setAgentId(string $agentId): EnterpriseIdentity {
		$this->agentId = $agentId;
		return $this;
	}
	
	/**
	 * 获取 经办人身份证
	 * @return string
	 */
	public function getAgentId():string {
		return $this->agentId;
	}




	/**
	 * 
	 * @return array
	 */
	public function toArray(): array{
		$arr = [];
		foreach(get_object_vars($this) as $p => $v){
			if( empty($v) ){
				continue;
			}
			if(is_object($v) && method_exists($v, 'toArray')){
				$arr[$p] = $v->toArray();
			}else{
				$arr[$p] = $v;
			}
		}
		return $arr;
	}

}
