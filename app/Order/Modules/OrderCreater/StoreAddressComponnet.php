<?php
/**
 * 门店收货地址组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Activity\Modules\Repository\ActivityThemeRepository;
use App\Lib\Common\LogApi;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\StoreAddress;
use App\Order\Modules\Repository\OrderUserAddressRepository;

class StoreAddressComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;

    //订单类型
    private $orderType;

    //收货地址
    private $address;
    public function __construct(OrderCreater $componnet,string $address)
    {
        $this->componnet = $componnet;
        $this->address =$address;
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

        $data =$this->componnet->getOrderCreater()->getDataSchema();

        if($this->orderType == OrderStatus::orderStoreService || $this->orderType == OrderStatus::orderActivityService){
            if($this->address==""){
                $this->getOrderCreater()->setError('店面地址配置不允许为空');
                $this->flag = false;
            }
        }
        if($this->orderType == OrderStatus::orderActivityService){
            $data = $this->componnet->getDataSchema();
            $activity = ActivityThemeRepository::getInfo(['activity_id'=>$data['activity']['activity_id']]);
            if(!$activity){
                $this->getOrderCreater()->setError('活动店面主题错误');
                $this->flag = false;
            }
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
        if($this->orderType != OrderStatus::orderActivityService && $this->orderType != OrderStatus::orderStoreService){
            return true;
        }
        $data =$this->getDataSchema();

        $addressData = [
            'order_no'=>$data['order']['order_no'],
            'consignee_mobile' =>$data['user']['user_mobile'],
            'name'=>$data['user']['realname'],
            'province_id'=>0,
            'city_id'=>0,
            'area_id'=>0,
            'address_info'=>$this->address,
            'create_time'=>time(),
        ];
        $id =OrderUserAddressRepository::add($addressData);

        if(!$id){
            LogApi::alert("OrderCreate:保存订单门店地址失败",$addressData,[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-Add-StoreAddress-error",$addressData);
            $this->getOrderCreater()->setError("OrderCreate-Add-StoreAddress-error");
            return false;
        }
        return true;
    }
}