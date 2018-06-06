<?php
/**
 * 订单创建器
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater;


use App\Order\Modules\Repository\Order\Creater\Component\NullComponent;

class OrderCreater implements OrderCreaterInterface
{

    /**
     * 组件列表
     * @var array
     */
    private $components = [];

    /**
     * @var OrderCreaterInterface
     */
    private $component;

    public function __construct()
    {
        $this->component = new NullComponent( $this );
    }

    /**
     * 注册组件
     * @param OrderCreaterInterface $componnet
     * @return OrderCreater
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function registerComponent( OrderCreaterComponent $component):OrderCreater {
        $this->components[] = $component;
        $component->setComponent( $this->component );
        $this->component = $component;
        return $this;
    }

    /**
     * 获取组件
     * @param string $name
     * @return OrderCreaterInterface
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function getComponent( string $name ): OrderCreaterInterface{
        return $this->skuComponent;
    }

	
	
	//-+------------------------------------------------------------------------
	// | 接口实现
	//-+------------------------------------------------------------------------
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
	public function getOrderCreater():OrderCreater{
		return $this;
	}
	
    /**
     * 过滤
     * @return bool
     */
	public function filter( array $params ): bool{
		return $this->component->filter();
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