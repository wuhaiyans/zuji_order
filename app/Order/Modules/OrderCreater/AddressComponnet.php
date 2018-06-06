<?php
/**
 * 收货地址主键
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Modules\Repository\OrderUserAddressRepository;

class AddressComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;

    //收货地址
    private $address;
    public function __construct(OrderCreater $componnet)
    {
        $this->componnet = $componnet;

    }

    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this->componnet->getOrderCreater();
    }
    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
    public function filter(): bool
    {
        $filter =$this->componnet->filter();
        //获取用户信息
        $schema =$this->componnet->getOrderCreater()->getUserComponnet()->getDataSchema();
        if(empty($schema['address'])){
            $this->getOrderCreater()->setError('收货地址不允许为空');
            $this->flag = false;
        }

        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        return $this->componnet->getDataSchema();
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        $data =$this->getDataSchema();
        $addressData = [
            'order_no'=>$data['order']['order_no'],
            'consignee_mobile' =>$data['address']['mobile']?$data['address']['mobile']:"",
            'name'=>$data['address']['name']?$data['address']['name']:"",
            'province_id'=>$data['address']['province_id']?$data['address']['province_id']:"",
            'city_id'=>$data['address']['city_id']?$data['address']['city_id']:"",
            'area_id'=>$data['address']['district_id']?$data['address']['district_id']:"",
            'address_info'=>$data['address']['address']?$data['address']['province_name']." ".$data['address']['city_name']." ".$data['address']['country_name']:"".$data['address']['address'],
            'create_time'=>time(),
        ];
        $id =OrderUserAddressRepository::add($addressData);
        if(!$id){
            $this->getOrderCreater()->setError("保存用户地址信息失败");
            return false;
        }
        return true;
    }
}