<?php
/**
 * 订单创建器组件抽象类
 * @access public
 * @author 
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater;


abstract class OrderCreaterComponent implements OrderCreaterInterface
{
	
	/**
	 *
	 * @var OrderCreaterComponent
	 */
	protected $component;


	/**
	 * 构造函数
	 */
	public function __construct( ){ }


    /**
     * 设置组件
     * @param OrderCreaterInterface $componnet	订单创建器组件
     */
    public function setComponent(OrderCreaterInterface $component ){
        $this->component = $component;
    }
	
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
	public function getOrderCreater():OrderCreater{
		return $this->component->getOrderCreater();
	}
	
    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
	public function filter( array $params ): bool{
		return $this->component->filter( $params );
	}

    /**
     * 获取数据结构
     * @return array
	 * []
     */
	public function getDataSchema(): array{
		return $this->component->getDataSchema();
	}

    /**
     * 创建
     * @return bool
     */
	public function create(): bool{
		return $this->component->create();
	}


}
