<?php
namespace App\Order\Models;
/**
 * 订单买断表模型
 * @author limin<limin@huishoubao.com.cn>
 */
use Illuminate\Database\Eloquent\Model;

class OrderBuyout extends Model
{
	protected $table = 'order_buyout';

    protected $primaryKey='id';

	const CREATED_AT = "create_time";

	const UPDATED_AT = "update_time";
	/**
	 * 默认使用时间戳戳功能
	 * @var bool
	 */
	public $timestamps = true;

    // create()时可以被赋值的属性。
    public $fillable = [
		'id', //主键id
		'type',//类型 0:用户买断;1:客服买断
		'buyout_no',//买断单编号
		'order_no', //订单编号
		'goods_no', //商品编号
		'user_id', //用户ID
		'goods_name',//设备名称
		'buyout_price', //买断价格
		'zujin_price',//结算租金
		'zuqi_number',//结算剩余期数
		'amount', //应付总金额
		'zujin', //结算租金
		'status', //买断状态
		'plat_id', //操作人员id
		'remark',//客服备注
		'create_time', //创建时间
		'update_time', //修改时间
	];

}