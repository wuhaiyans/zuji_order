<?php

/**
 * 蚂蚁金服 金融科技 可信存证 存证地点 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Lib\Alipay\Notary;

/**
 * Location 存证地点 类
 * <p><b>注意：</b></p>
 * <ul>
 * <li>1、该地址标记了真实用户客户端的地点</li>
 * <li>2、IP地址必须，其他属性可选</li>
 * </ul>
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Location {
	/**
	 * 操作IP地址
	 * @var string
	 */
	private $ip='';
	/**
	 *操作Wi-Fi物理地址
	 * @var string
	 */
	private $wifiMac='';
	/**
	 * 操作IMEI
	 * @var string
	 */
	private $imei='';
	/**
	 * 操作IMSI
	 * @var string
	 */
	private $imsi='';
	/**
	 * 维度
	 * @var string
	 */
	private $latitude='';
	/**
	 * 经度
	 * @var string
	 */
	private $longitude='';
	/**
	 * 扩展属性字段
	 * @var string
	 */
	private $properties='';
	
	public function getIp():string {
		return $this->ip;
	}

	public function getWifiMac():string {
		return $this->wifiMac;
	}

	public function getImei():string {
		return $this->imei;
	}

	public function getImsi():string {
		return $this->imsi;
	}

	public function getLatitude():string {
		return $this->latitude;
	}

	public function getLongitude():string {
		return $this->longitude;
	}

	public function getProperties():string {
		return $this->properties;
	}

	public function setIp(string $ip):Location {
		$this->ip = $ip;
		return $this;
	}

	public function setWifiMac(string $wifiMac):Location {
		$this->wifiMac = $wifiMac;
		return $this;
	}

	public function setImei(string $imei):Location {
		$this->imei = $imei;
		return $this;
	}

	public function setImsi(string $imsi):Location {
		$this->imsi = $imsi;
		return $this;
	}

	public function setLatitude(string $latitude) {
		$this->latitude = $latitude;
		return $this;
	}

	public function setLongitude(string $longitude):Location {
		$this->longitude = $longitude;
		return $this;
	}

	public function setProperties(string $properties):Location {
		$this->properties = $properties;
		return $this;
	}

	/**
	 * 转化成数组
	 * @param bool $empty  是否返回空值属性
	 * @return array
	 */
	public function toArray( bool $empty=true ): array{
		$data = [
			'ip'		=> $this->ip,
			'wifiMac'	=> $this->wifiMac,
			'imei'		=> $this->imei,
			'imsi'		=> $this->imsi,
			'latitude'	=> $this->latitude,
			'longitude' => $this->longitude,
			'properties' => $this->properties,
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
	
	public static function fromArray( array $data ):Location{
		$self = new self();
		$self->ip		= $data['ip'];
		$self->wifiMac	= $data['wifiMac'];
		$self->imei		= $data['imei'];
		$self->imsi		= $data['imsi'];
		$self->latitude	= $data['latitude'];
		$self->longitude= $data['longitude'];
		$self->properties = $data['properties'];
		return $self;
	}
	
	
}
