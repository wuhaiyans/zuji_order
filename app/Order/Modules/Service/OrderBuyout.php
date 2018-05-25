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
    public static function create($array)
	{
		$data = filter_array($array,[
				'order_no'=>'required',
				'goods_no'=>'required',
				'user_id'=>'required',
				'buyout_price'=>'required',
		]);
		if(count($data)!=4){
			return false;
		}
		return OrderBuyoutRepository::create($data);
	}
}
