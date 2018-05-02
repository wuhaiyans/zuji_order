<?php
namespace App\Order\Modules\Service\order_creater;
use App\Order\Modules\Service\OrderCreater;
/**
 * 收货地址 组件
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class AddressComponnet implements OrderCreaterComponnet {
    
	private $flag = true;
	
	/**
	 * 组件
	 * @var \oms\order_creater\OrderCreaterComponnet
	 */
    private $componnet = null;
	
	/**
	 * 收货地址 ID
	 * @var int 
	 */
    private $address_id = 0;
	
	/**
	 * 联系人 姓名
	 * @var string 
	 */
    private $name = '';
	/**
	 * 联系人 电话
	 * @var string 
	 */
    private $mobile = '';
	/**
	 * 详细地址
	 * @var string 
	 */
    private $address = '';
	/**
	 * 省份ID
	 * @var string 
	 */
    private $province_id = 0;
	private $province_name = '';
	/**
	 * 城市ID
	 * @var string 
	 */
    private $city_id = 0;
	private $city_name = '';
	/**
	 * 区县ID
	 * @var string 
	 */
    private $country_id = 0;
	private $country_name = '';
	
    
    public function __construct(OrderCreaterComponnet $componnet, int $address_id) {
        $this->componnet = $componnet;
		
		// 查询收货地址
		$address_table = \hd_load::getInstance()->table('member/member_address');
		$fields = ['id','mid','name','mobile','district_id','address','status'];
		$info = $address_table->field($fields)->where(['id'=>$address_id])->find();
		if( !$info ){
			throw new ComponnetException('[创建订单]获取收货地址失败');
		}
		
		// 用户ID
		$this->user_id = $this->get_order_creater()->get_user_componnet()->get_user_id();
		if( $this->user_id != $info['mid'] ){
			throw new ComponnetException('[创建订单]获取收货地址ID错误');
		}
		if( $info['status'] != 1 ){
			throw new ComponnetException('[创建订单]获取收货地址错误');
		}
		
		// 赋值
		$this->address_id = intval($info['id']);
		$this->name = $info['name'];
		$this->mobile = $info['mobile'];
		$this->address = $info['address'];
		$this->status = intval($info['status'])?1:0;
		$this->country_id = intval($info['district_id']);// 区县ID
		
		// 查询 省市区ID和名称
		$district_server = \hd_load::getInstance()->service('admin/district');
		
		//查询区
		$country = $district_server->get_info($this->country_id);
		if( !$country ){
			throw new ComponnetException('[创建订单]获取收货地址错误');
		}
		$this->country_name = $country['name'];
		
		//查询市
		$city    = $district_server->get_info($country['parent_id']);
		if( !$city ){
			throw new ComponnetException('[创建订单]获取收货地址错误');
		}
		$this->city_id = $city['id'];
		$this->city_name = $city['name'];
		
		//查询市
		$province    = $district_server->get_info($city['parent_id']);
		if( !$province ){
			throw new ComponnetException('[创建订单]获取收货地址错误');
		}
		$this->province_id = $province['id'];
		$this->province_name = $province['name'];
		
    }
    
    public function get_order_creater(): OrderCreater {
        return $this->componnet->get_order_creater();
    }
    
    public function filter():bool {
		$filter_b = $this->componnet->filter();
        //var_dump( '过滤收货地址...' );
		
        return $this->flag && $filter_b;
    }
    
	public function get_data_schema(): array{
		$schema = $this->componnet->get_data_schema();
		return array_merge($schema,[
			'address' => [
				'user_id' => $this->user_id,
				'name' => $this->name,
				'mobile' => $this->mobile,
				'address' => $this->address,
				'province_id' => $this->province_id,
				'province_name' => $this->province_name,
				'city_id' => $this->city_id,
				'city_name' => $this->city_name,
				'country_id' => $this->country_id,
				'country_name' => $this->country_name,
			]
		]);
	}
	
    public function create():bool {
        if( !$this->flag ){
            return false;
        }
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
		
        //var_dump( '---------------------------保存收货地址信息...' );
        
		// 保存收货地址
        $order_id = $this->componnet->get_order_creater()->get_order_id();
		$data = [
			'order_id' => $order_id,
			'user_id' => $this->user_id,
			'name' => $this->name,
			'mobile' => $this->mobile,
			'address' => $this->address,
			'province_id' => $this->province_id,
			'city_id' => $this->city_id,
			'country_id' => $this->country_id,
		];
		$order2_address_table = \hd_load::getInstance()->table('order2/order2_address');
		$address_id = $order2_address_table->add($data);
		if( $address_id<1 ){
			$this->get_order_creater()->set_error('保存收货地址信息失败');
			return false;
		}
		
		// address_id 写入订单表
		$data = [
			'address_id' => $address_id,
		];
		$order_table = \hd_load::getInstance()->table('order2/order2');
		$b = $order_table->where(['order_id'=>$order_id])->save($data);
		if( !$b ){
			$this->get_order_creater()->set_error('保存收货地址ID失败');
			return false;
		}
		
        return true;
    }

}
