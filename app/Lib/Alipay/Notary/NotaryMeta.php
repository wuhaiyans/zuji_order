<?php

/**
 * 蚂蚁金服 金融科技 可信存证 存证元数据 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Lib\Alipay\Notary;

/**
 * NotaryMeta 存证元数据 类
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class NotaryMeta {
	
	/**
	 * 账号标识
	 * @var string
	 */
	private $accountId = '';
	
	/**
	 * 事务ID
	 * @var string
	 */
	private $token = '';
	
	/**
	 * 事务阶段
	 * @var string
	 */
	private $phase = '';
	
	/**
	 * 存证实体，个或者商家
	 * @var CustomerIdentity
	 */
	private $entity;
	
	/**
	 * 存证时间；格式：yy-MM-dd hh:mm:ss
	 * @var string 
	 */
	private $timestamp = '';
	
	/**
	 * 存证地点
	 * @var Location
	 */
	private $location;
	
	/**
	 * 扩展属性字段
	 * @var string
	 */
	private $properties = '';
	
	
	public function getAccountId(): string {
		return $this->accountId;
	}

	public function getToken(): string {
		return $this->token;
	}

	public function getPhase(): string {
		return $this->phase;
	}

	public function getEntity(): CustomerIdentity {
		return $this->entity;
	}

	public function getTimestamp(): string {
		return $this->timestamp;
	}

	public function getLocation(): Location {
		return $this->location;
	}

	public function getProperties(): string {
		return $this->properties;
	}

	public function setAccountId(string $accountId) {
		$this->accountId = $accountId;
		return $this;
	}

	public function setToken(string $token) {
		$this->token = $token;
		return $this;
	}

	public function setPhase(string $phase) {
		$this->phase = $phase;
		return $this;
	}

	public function setEntity(Identity $entity) {
		$this->entity = $entity;
		return $this;
	}

	public function setTimestamp(string $timestamp) {
		$this->timestamp = $timestamp;
		return $this;
	}

	public function setLocation(Location $location) {
		$this->location = $location;
		return $this;
	}

	public function setProperties(string $properties) {
		$this->properties = $properties;
		return $this;
	}


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
