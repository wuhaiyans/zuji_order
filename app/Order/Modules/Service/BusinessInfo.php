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
        return $this->data['business_type'] = $type;
    }
    public function setBusinessName( string $name )
    {
        return $this->data['business_name'] = $name;
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
        return $this->data['stateFlow'] = $state_flow;
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
    public function getStateFlow(){
        // 业务状态
        return [
                'stateFlow'  =>  [
                        'status' => 'A',
                        'name' => '申请',
                    ],
                    [
                        'status' => 'B',
                        'name' => '审核',
                    ],
                    [
                        'status' => 'C',
                        'name' => '检测',
                    ],
                    [
                        'status' => 'D',
                        'name' => '完成',
                    ],
                'cancelStateFlow'  =>  [
                    'status' => 'A',
                    'name' => '申请',
                ],
                [
                    'status' => 'B',
                    'name' => '审核',
                ],
                [
                    'status' => 'C',
                    'name' => '取消',
                ],


            ];
    }
    public function toArray( )
    {
        return $this->data;
    }

}