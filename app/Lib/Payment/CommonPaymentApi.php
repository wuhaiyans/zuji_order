<?php
namespace App\Lib\Payment;
/**
 * 公共支付接口
 *
 * @author 
 */
class CommonPaymentApi extends \App\Lib\BaseApi {
	/**
	 * 统一支付页面URL接口
	 * @param array $params
	 * [
	 *		'out_payment_no'	=> '',	//【必选】string 业务支付唯一编号
	 *		'payment_amount'	=> '',	//【必选】int 交易金额；单位：分
	 *		'payment_fenqi'		=> '',	//【必选】int 分期数
	 *		'channel_type'	=> '',	//【必选】int 支付渠道
	 *		'name'			=> '',	//【必选】string 交易名称
	 *		'back_url'		=> '',	//【必选】string 后台通知地址
	 *		'front_url'		=> '',	//【必选】string 前端回跳地址
	 *		'user_id'		=> '',	//【可选】int 业务平台yonghID
	 * ]
	 * @return mixed false：失败；array：成功
	 * [
	 *		'payment_url' => '',//支付链接
	 * ]
	 * @throws \Exception			请求失败时抛出异常
	 */
	public static function pageUrl( array $params ){
		$info = self::request(\env('PAY_APPID'), \env('PAY_API'),'pay.payment.url', '1.0', $params);
		if( !isset($info['url']) )
		{
			$info['url'] = $info['payment_url'];
		}
		return $info;
	}

	/**
	 * 统一支付查询接口
	 * @param array $params
	 * [
	 *		'payment_no'		=> '',	//【必选】string 支付系统支付编号
	 *		'out_payment_no'	=> '',	//【必选】string 业务系统支付编号
	 * ]
	 * @return mixed false：接口请求失败；array：支付信息
	 * [
	 *		'payment_no'		=> '',	//【必选】string 支付系统支付编号
	 *		'out_payment_no'	=> '',	//【必选】string 业务系统支付编号
	 *		'status'			=> '',	//【必选】string success：支付成功；init：初始化；success：成功；failed：失败；finished：完成；closed：关闭； processing：处理中；
	 *		'trade_time'			=> '',	//【必选】string 时间戳
	 * ]
	 * @throws \Exception			请求失败时抛出异常
	 */
	public static function query( array $params ){
		return self::request(\env('PAY_APPID'), \env('PAY_API'),'pay.payment.query', '1.0', $params);
	}

}
