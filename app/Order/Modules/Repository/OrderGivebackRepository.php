<?php
namespace App\Order\Modules\Repository;

use App\Order\Models\OrderGiveback;

class OrderGivebackRepository 
{
	/**
	 * 订单还机单model类
	 * @var obj OrderGiveback
	 */
	protected $order_giveback_model;
	/**
	 * 构造方法
	 */
	public function __construct(  ){
		$this->order_giveback_model = new OrderGiveback();
	}
    public function create( $data ){
		return $this->order_giveback_model->insertGetId( $data );
	}
}