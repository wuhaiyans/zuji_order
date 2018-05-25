<?php
namespace App\Order\Modules\Service;

use App\Order\Modules\Repository\OrderBuyoutRepository;
use App\Order\Modules\Inc\OrderBuyoutStatus;

class OrderBuyout
{
	/**
	 * 订单还机数据处理仓库
	 * @var obj
	 */
	public function __construct(  ) {
	}
    /**
     * 保存还机单数据
     * @param $data
     * @return id
     */
    public function create($data)
	{
		$data = filter_array($data, [
				'order_no' => 'required',//订单编号
				'goods_no' => 'required',//商品编号
				'user_id' => 'required',//用户id
				'logistics_no' => 'required',//物流单号
				'giveback_no' => 'required',//还机单编号
				'status' => 'required',//订单状态
		]);
	}
}
