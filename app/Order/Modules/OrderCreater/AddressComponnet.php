<?php
/**
 * 收货地址主键
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\StoreAddress;
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

        $appid =$this->getOrderCreater()->getAppid();
        $this->address =StoreAddress::getStoreAddress($appid);

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

        if(empty($schema['address']) && ! $this->address){
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

        if(isset($data['address']['province_name']) && isset($data['address']['city_name']) && isset($data['address']['country_name']) ){
            $address_info = $data['address']['province_name']." ".$data['address']['city_name']." ".$data['address']['country_name'].' '.$data['address']['address'];
        }elseif(isset($data['address']['address'])){
            $address_info =  $data['address']['address'];
        }else{
            $address_info = $this->address;
        }
        $addressData = [
            'order_no'=>$data['order']['order_no'],
            'consignee_mobile' =>isset($data['address']['mobile'])?$data['address']['mobile']:$data['user']['user_mobile'],
            'name'=>isset($data['address']['name'])?$data['address']['name']:$data['user']['user_mobile'],
            'province_id'=>isset($data['address']['province_id'])?$data['address']['province_id']:0,
            'city_id'=>isset($data['address']['city_id'])?$data['address']['city_id']:0,
            'area_id'=>isset($data['address']['district_id'])?$data['address']['district_id']:0,
            'address_info'=>$address_info,
            'create_time'=>time(),
        ];
        $id =OrderUserAddressRepository::add($addressData);
        if(!$id){
            LogApi::error(config('app.env')."[下单]保存用户地址信息失败",$addressData);
            $this->getOrderCreater()->setError("保存用户地址信息失败");
            return false;
        }
        return true;
    }
}