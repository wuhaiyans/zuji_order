<?php
/**
 * 订单创建器
 * @access public
 * @author  <>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater;


class OrderCreater implements OrderCreaterInterface
{
	
	/**
	 * 用户组件
	 * @var OrderCreaterComponnet
	 */
	private $userComponnet;
	
	/**
	 * 商品组件
	 * @var OrderCreaterComponnet
	 */
	private $skuComponnet;


	/**
     *
     * 设置  组件
     * @param UserComponnet $userComponnet
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
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
	public function filter(): bool{
		
		return true;
	}

    /**
     * 获取数据结构
     * @return array
	 * []
     */
	public function getDataSchema(): array{
		return [];
	}

    /**
     * 创建
     * @return bool
     */
	public function create(): bool{
		 
		return true;
	}
	
}