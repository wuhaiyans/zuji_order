<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;
use App\Order\Models\OrderUserAddress;

/**
 * Address
 *
 * @author Administrator
 */
class Address {
	//put your code here
    /**
     *
     * @var OrderUserAddress
     */
    private $model = [];


    /**
     * 构造函数
     * @param array $data 订单原始数据
     */
    public function __construct(OrderUserAddress $OrderUserAddress) {
        $this->model = $OrderUserAddress;
    }

    /**
     * 读取用户原始数据
     * @return array
     */
    public function getData():array{
        return $this->model->toArray();
    }
    /**
     * 获取用户收货信息
     * <p>当订单不存在时，抛出异常</p>
     * @param int   	$order_no		    订单编号
     * @param int		$lock		锁
     * @return \App\Order\Modules\Repository\Order\Address
     * @return  bool
     */
    public static function getByOrderNo( $order_no, int $lock=0 ) {
        $builder = \App\Order\Models\OrderUserAddress::where([
            ['order_no', '=', $order_no],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $user_info = $builder->first();
        if(!$user_info ){
            return false;
        }
        return new self($user_info );
    }

}
