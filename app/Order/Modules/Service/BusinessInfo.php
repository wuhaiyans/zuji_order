<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23 0023
 * Time: 下午 6:57
 */

namespace App\Order\Modules\Service;


class BusinessInfo
{
    private $data = [];
    public function __construct()
    {
    }

    public function setBusinessType( string $type )
    {
        $this->data['business_type'] = $type;
    }
    public function setBusinessName( string $name )
    {
    }
    public function setBusinessNo( string $no )
    {
    }

    public function setOrderInfo( array $order_info )
    {
    }
    public function setGoodsInfo( array $goods_info )
    {
    }
    public function setStateFlow( array $state_flow )
    {
    }
    public function setStatus( string $status )
    {
    }
    public function setStatusText( string $status_text )
    {
    }
    public function setLogisticsForm( array $logistics_form )
    {
    }
    public function setLogisticsInfo( array $logistics_info )
    {
    }

    public function toArray( )
    {
        return $this->data;
    }

}