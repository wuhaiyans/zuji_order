<?php
/**
 * 订单创建器接口
 * @access public
 * @author 
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater;


interface OrderCreaterInterface
{
    
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderCreater;

    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @param array $params     业务参数集合
     * [
     *      'user_id'   => ''，         //【必须】int 用户ID
     *      'sku_list'  => [            //【必须】array sku列表参数，长度至少为1
     *		    [
     *			    'sku_id'	=> '',	//【必须】int SKU ID
     *			    'num'		=> '',	//【必须】int 数量
     *		    ]
     *      ]
     * ]
     * @return bool
     */
    public function filter( array $params ): bool;

    /**
     * 获取数据结构
     * @return array
	 * []
     */
    public function getDataSchema(): array;

    /**
     * 创建
     * @return bool
     */
    public function create(): bool;


}
