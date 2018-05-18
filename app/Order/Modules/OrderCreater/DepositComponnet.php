<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


class DepositComponnet implements OrderCreater
{
    //组件
    private $componnet;
    public function __construct(OrderCreater $componnet,int $payType,$certified_flag=true)
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
        var_dump("deposit组件 -filter");
        return $this->componnet->filter();
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        var_dump("deposit组件 -get_data_schema");
        return [];
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        $this->componnet->create();
        var_dump("deposit组件 -create");
        return true;
    }

}