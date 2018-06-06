<?php
/**
 * 收货地址组件
 *
 * 【注意：】如果业务参数中没有指定收货地址ID时，则不创建
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater\Component;

use App\Order\Modules\Repository\Order\Creater\OrderCreaterComponent;

class AddressComponent extends OrderCreaterComponent
{


    /**
     * 数据过滤
     * @param array
     * [
     *      'address_id' => '', //【可选】收货地址ID
     * ]
     * @return bool
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function filter( array $params ): bool
    {
        $b = $this->componnet->filter( $params );
        if( !$b ){
            return false;
        }

        //
        return true;
    }

    /**
     * 读取基本信息
     * @return array
     * [
     *      '' => '',
     *      '' => '',
     *      '' => '',
     *      '' => '',
     *      '' => '',
     * ]
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function getDataSchema(): array
    {
        return [
            'address_info' => [
                'id' => '',
                '' => '',
                '' => '',
                '' => '',
            ],
        ];

    }

    /**
     * 创建
     * @return bool
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     */
    public function create(): bool
    {
        if( !parent::create() ){
            return false;
        }

        return true;
    }

}