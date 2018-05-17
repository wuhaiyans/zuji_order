<?php
/**
 * 支付环节 数据模型
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 支付 数据模型 类
 * 定义 支付环节 统一标准数据访问接口
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderPayPaymentModel extends Model
{

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
	
    protected $table = 'order_pay_payment';

    protected $primaryKey = 'payment_no';

	
	/**
	 * 创建支付环节记录
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param	array	$data		支付环节初始化数据
	 * [
	 *		'payment_no'		=> '',	//[必选]string 
	 *		'out_payment_no'	=> '',	//[必选]string 
	 *		'payment_time'		=> '',	//[必选]string 
	 * ]
	 * @return	bool				true：创建成功；false：创建失败
	 */
	public function create( array $data ){
		return parent::insert( $data );
	}
	
}