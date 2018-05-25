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

    // create()时可以被赋值的属性。
    public $fillable = [
		'id', //主键id
		'order_no', //订单编号
		'goods_no', //商品编号
		'user_id', //用户ID
		'buyout_price', //买断价格
		'status', //买断状态
		'plat_id', //操作人员id
		'remark',//客服备注
		'create_time', //创建时间
		'update_time', //修改时间
	];
	/**
	 * 默认使用时间戳戳功能
	 * @var bool
	 */
	public $timestamps = true;
	
}