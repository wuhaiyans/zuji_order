<?php
/**
 * 订单创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


class OrderComponnet implements OrderCreater
{
    //订单编号
    private $orderNo = null;
    //用户组件
    private $userComponnet =null;
    //sku组件
    private $skuComponnet =null;

    public function __construct( $orderNo=null ) {
        $this->orderNoNo = $orderNo;
    }

    /**
     *
     * 设置 User组件
     * @param UserComponnet $user_componnet
     * @return OrderCreater
     */
    public function setUserComponnet(UserComponnet $userComponnet){
        $this->userComponnet = $userComponnet;
        return $this;
    }
    /**
     * 获取 User组件
     * @return UserComponnet
     */
    public function getUserComponnet(){
        return $this->userComponnet;
    }

    /**
     * 设置 Sku组件
     * @param SkuComponnet $sku_componnet
     * @return OrderCreater
     */
    public function setSkuComponnet(SkuComponnet $skuComponnet){
        $this->skuComponnet = $skuComponnet;
        return $this;
    }
    /**
     * 获取 Sku组件
     * @return SkuComponnet
     */
    public function getSkuComponnet(): SkuComponnet{
        return $this->skuComponnet;
    }
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderCreater
    {
        var_dump("订单组件 -get_order_creater");
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
        var_dump("订单组件 -filter");
        return true;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        var_dump("订单组件 -get_data_schema");
        return [];
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {
        var_dump("订单组件 -create");
        return true;
    }
}