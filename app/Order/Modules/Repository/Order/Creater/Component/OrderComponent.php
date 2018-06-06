<?php
/**
 * 订单组件
 * @access public
 * @author 
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater\Component;

use App\Order\Modules\Repository\Order\Creater\OrderCreaterComponent;

class OrderComponent extends OrderCreaterComponent
{


    /**
     * 过滤
     * @return bool
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
     * 基本信息
     * @return array
     * [
     *      '' => '',
     *      '' => '',
     *      '' => '',
     *      '' => '',
     *      '' => '',
     * ]
     */
    public function getDataSchema(): array
    {
        return [
            'order_info' => [
                'order_no' => '',
                '' => '',
                '' => '',
                '' => '',
            ],
        ];

    }

    /**
     * 
     * @return bool
     */
    public function create(): bool
    {
        if( !parent::create() ){
            return false;
        }

        return true;
    }

}