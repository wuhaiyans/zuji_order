<?php
/**
 * 收货地址主键
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


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
        //获取用户信息
        $schema =$this->componnet->getDataSchema();
        $this->address =$schema['address'];
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

        if(empty($this->address)){
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
        $this->componnet->create();
        var_dump("AddressComponnet -create");
        return true;
//        if( !$this->flag ){
//            return false;
//        }
//        $b = $this->componnet->create();
//        if( !$b ){
//            return false;
//        }
//
//        //var_dump( '---------------------------保存收货地址信息...' );
//
//        // 保存收货地址
//        $order_id = $this->componnet->get_order_creater()->get_order_id();
//        $data = [
//            'order_id' => $order_id,
//            'user_id' => $this->user_id,
//            'name' => $this->name,
//            'mobile' => $this->mobile,
//            'address' => $this->address,
//            'province_id' => $this->province_id,
//            'city_id' => $this->city_id,
//            'country_id' => $this->country_id,
//        ];
//        $order2_address_table = \hd_load::getInstance()->table('order2/order2_address');
//        $address_id = $order2_address_table->add($data);
//        if( $address_id<1 ){
//            $this->get_order_creater()->set_error('保存收货地址信息失败');
//            return false;
//        }
//
//        // address_id 写入订单表
//        $data = [
//            'address_id' => $address_id,
//        ];
//        $order_table = \hd_load::getInstance()->table('order2/order2');
//        $b = $order_table->where(['order_id'=>$order_id])->save($data);
//        if( !$b ){
//            $this->get_order_creater()->set_error('保存收货地址ID失败');
//            return false;
//        }
//
//        return true;
//    }
        return true;
    }
}