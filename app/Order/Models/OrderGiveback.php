<?php

/**
 *
 *  订单设备列表
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderGiveback extends Model
{
	protected $table = 'order_giveback';

    protected $primaryKey='id';

    // create()时可以被赋值的属性。
    protected $fillable = [
		'id', //主键id
		'order_no', //订单编号
		'goods_no', //商品编号
		'user_id', //用户ID
		'trade_no', //交易流水号
		'out_trade_no', //返回的交易流水号
		'status', //还机列表状态 0 默认
		'instalment_num', //剩余还款的分期数
		'instalment_amount', //剩余还款的分期总金额（分）
		'payment_status', //支付状态 0默认 
		'payment_time', //支付时间
		'logistics_no', //物流编号
		'evaluation_status', //检测结果状态
		'evaluation_remark', //检测结果备注
		'evaluation_time', //检测时间
		'compensate_amount', //赔偿金额
		'create_time', //创建时间
		'update_time', //更新时间
		'remark', //备注
	];
	/**
	 * 默认使用时间戳戳功能
	 *
	 * @var bool
	 */
	public $timestamps = false;
	
}