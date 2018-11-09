<?php
/**
 * 收货地址组件
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\StoreAddress;
use App\Order\Modules\Repository\OrderUserAddressRepository;

class AddressComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;

    //订单类型
    private $orderType;

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
        $this->orderType = $this->componnet->getOrderCreater()->getOrderType();

        //如果是线下领取订单 不走用户组件
        if($this->orderType == OrderStatus::orderActivityService){
            return $this->flag && $filter;
        }
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

        //如果是线下领取订单 不走用户组件
        if($this->orderType == OrderStatus::orderActivityService){
            return true;
        }

        $data =$this->getDataSchema();

        if(isset($data['address']['province_name']) && isset($data['address']['city_name']) && isset($data['address']['country_name']) ){
            $address_info = $data['address']['province_name']." ".$data['address']['city_name']." ".$data['address']['country_name'].' '.$data['address']['address'];
        }else{
            $address_info =  $data['address']['address'];
        }

        $realname = $data['user']['realname']?$data['user']['realname']: substr($data['user']['user_mobile'],0,3)."****".substr($data['user']['user_mobile'],7,11);
        $addressData = [
            'order_no'=>$data['order']['order_no'],
            'consignee_mobile' =>isset($data['address']['mobile'])?$data['address']['mobile']:$data['user']['user_mobile'],
            'name'=>isset($data['address']['name'])?$data['address']['name']:$realname ,
            'province_id'=>isset($data['address']['province_id'])?$data['address']['province_id']:0,
            'city_id'=>isset($data['address']['city_id'])?$data['address']['city_id']:0,
            'area_id'=>isset($data['address']['district_id'])?$data['address']['district_id']:0,
            'address_info'=>$address_info,
            'create_time'=>time(),
        ];
        $id =OrderUserAddressRepository::add($addressData);
        if(!$id){
            LogApi::error(config('app.env')."OrderCreate-Add-error",$addressData);
            $this->getOrderCreater()->setError("OrderCreate-Add-error");
            return false;
        }
        return true;
    }
}