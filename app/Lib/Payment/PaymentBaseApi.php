<?php
namespace App\Lib\Payment;
/**
 * 支付接口基类
 * 定义了 系统之间交互接口基本方式
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class PaymentBaseApi extends \App\Lib\BaseApi {
	
	/**
	 * 接口 URL 地址
	 * @return string
	 */
	protected static function getUrl():string{
		return env('');
	}
	
}
