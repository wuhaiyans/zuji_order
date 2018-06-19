<?php
/**
 * 支付 数据模型
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */
namespace App\Order\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 支付 数据模型 类
 * 定义 订单系统 支付阶段 统一标准数据访问接口
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class OrderPayModel extends Model
{

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
	
    protected $table = 'order_pay';

    protected $primaryKey='id';

	protected $fillable = ['user_id','business_type','business_no','status','create_time','update_time','payment_status','payment_channel','payment_amount','payment_fenqi','payment_no','withhold_status','withhold_channel','withhold_no','fundauth_status','fundauth_channel','fundauth_amount','fundauth_no'];
    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp() {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     *
     * @param DateTime|int $value
     * @return DateTime|int
     */
    public function fromDateTime($value) {
        return $value;
    }


	/**
	 * 创建支付记录
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param	array	$data		支付初始化数据
	 * [
	 *		'business_type'		=> '',	//[必选]string 
	 *		'business_no'		=> '',	//[必选]string 
	 *		'status'			=> '',	//[必选]string 
	 *		'create_time'		=> '',	//[必选]string 
	 *		// 支付
	 *		'payment_status'	=> '',	//[可选]string 
	 *		'payment_channel'	=> '',	//[可选]string 
	 *		'payment_amount'	=> '',	//[可选]string 
	 *		'payment_fenqi'		=> '',	//[可选]string 
	 *		// 代扣签约
	 *		'withhold_status'	=> '',	//[可选]string 
	 *		'withhold_channel'	=> '',	//[可选]string 
	 *		// 资金预授权
	 *		'fundauth_status'	=> '',	//[可选]string 
	 *		'fundauth_channel'	=> '',	//[可选]string 
	 *		'fundauth_amount'	=> '',	//[可选]string 
	 * ]
	 * @return	bool				true：创建成功；false：创建失败
	 */
	public static function create( array $data ){
		return parent::insert( $data );
	}
	
	/**
	 * 取消 支付记录
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param	string	$business_type		业务类型
	 * @param	string	$business_no		业务编号
	 * @return	bool				true：成功；false：失败
	 */
	public function cancel(string $business_type, string $business_no){
		return true;
	}
	
}