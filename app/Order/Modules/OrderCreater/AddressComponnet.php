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
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        return true;
    }
}