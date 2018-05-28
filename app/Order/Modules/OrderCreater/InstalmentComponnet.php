<?php
/**
 * 分期组件创建构造器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Order\Controllers\Api\v1\InstalmentController;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Service\OrderInstalment;

class InstalmentComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    private $payType;


    public function __construct(OrderCreater $componnet,int $payType)
    {
        $this->componnet = $componnet;
        $this->payType =$payType;
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
        //统一过滤
        $filter =  $this->componnet->filter();
        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema =$this->componnet->getDataSchema();
        $instalment['instalment']="";
        //分期单信息
        if($this->payType!=PayInc::FlowerStagePay && $schema['order']['zuqi_type'] ==2){
            $instalment =OrderInstalment::get_data_schema($schema);
        }
        return array_merge($schema,['instalment'=>$instalment['instalment']]);
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {

        $schema =$this->componnet->getDataSchema();
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        if($this->payType!=PayInc::FlowerStagePay && $schema['order']['zuqi_type'] ==2) {
            $instalment = OrderInstalment::create($schema);
            if (!$instalment) {
                return false;
            }
        }
        return true;
    }
}