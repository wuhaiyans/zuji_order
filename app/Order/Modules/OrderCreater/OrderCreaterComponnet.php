<?php
/**
 * 订单创建器组件抽象类
 * @access public
 * @author 
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


abstract class OrderCreaterComponnet implements OrderCreaterInterface
{
	
	/**
	 *
	 * @var OrderCreaterComponnet 
	 */
	protected $componnet;


	/**
	 * 构造函数
	 * @param OrderCreaterComponnet $componnet	订单创建器组件
	 */
	public function __construct( OrderCreaterInterface $componnet )
    {
        $this->componnet = $componnet;
    }
	
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
	public function getOrderCreater():OrderCreater{
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
	public function filter(): bool{
		return $this->componnet->filter(); 
	}

    /**
     * 获取数据结构
     * @return array
	 * []
     */
	public function getDataSchema(): array{
		return $this->componnet->getDataSchema();
	}

    /**
     * 创建
     * @return bool
     */
	public function create(): bool{
		return $this->componnet->create();
	}


}
