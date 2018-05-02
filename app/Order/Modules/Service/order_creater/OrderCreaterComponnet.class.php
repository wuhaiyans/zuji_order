<?php

/*
 * 
 */

namespace App\Order\Modules\Service\order_creater;
/**
 * 
 *
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
interface OrderCreaterComponnet {
    
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function get_order_creater(): OrderCreater;
    
    /**
     * 过滤
	 * <p>注意：</p>
	 * <p>在过滤过程中，可以修改下单需要的元数据</p>
	 * <p>组件之间的过滤操作互不影响</p>
	 * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
    public function filter(): bool;
	
	/**
	 * 获取数据结构
	 * @return array
	 */
	public function get_data_schema(): array;
    
    /**
     * 
     * @return bool
     */
    public function create(): bool;
    
}
