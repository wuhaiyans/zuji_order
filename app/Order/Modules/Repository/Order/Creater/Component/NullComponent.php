<?php
/**
 * 空组件
 *
 * 【注意：】该组件什么都不做，直接返回正确结果
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater\Component;

use App\Order\Modules\Repository\Order\Creater\OrderCreater;
use App\Order\Modules\Repository\Order\Creater\OrderCreaterComponent;

class NullComponent extends OrderCreaterComponent
{

    public function __construct( OrderCreater $creater )
    {
        $this->component = $creater;
    }

    /**
     * 数据过滤
     * @param array     业务参数
     * @return bool
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function filter( array $params ): bool
    {
        //
        return true;
    }

    /**
     * 读取基本信息
     * @return array    空数组
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function getDataSchema(): array
    {
        return [];

    }

    /**
     * 创建
     * @return bool
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function create(): bool
    {
        return true;
    }

}