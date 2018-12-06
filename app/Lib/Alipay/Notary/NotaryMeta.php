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

	public function setEntity(CustomerIdentity $entity) {
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


	/**
	 * 转化成数组
	 * @param bool $empty  是否返回空值属性
	 * @return array
	 */
	public function toArray( bool $empty=true ): array{
		$entity = $location = null;
		if( $this->entity ){
			$entity = $this->entity->toArray($empty);
		}
		if( $this->entity ){
			$location = $this->location->toArray($empty);
		}
		
		$data = [
			'accountId'		=> $this->accountId,
			'token'			=> $this->token,
			'phase'			=> $this->phase,
			'entity'		=> $entity,
			'timestamp'		=> $this->timestamp,
			'location'		=> $location,
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
	
	public static function fromArray( array $data ): NotaryMeta{
		$entity = $location = null;
		if( $data['entity'] ){
			$entity = CustomerIdentity::fromArray($data['entity']);
		}
		if( $data['location'] ){
			$location = Location::fromArray($data['location']);
		}
		
		$self = new self();
		$self->accountId	= $data['accountId'];
		$self->token		= $data['token'];
		$self->phase		= $data['phase'];
		$self->entity		= $entity;
		$self->timestamp	= $data['timestamp'];
		$self->location		= $location;
		$self->properties	= $data['properties'];
		return $self; 
	}
	
	
}
