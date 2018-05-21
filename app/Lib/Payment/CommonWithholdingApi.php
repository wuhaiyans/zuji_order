<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 *
 * 代扣接口
 * @author zjh
 */
class CommonWithholdingApi extends \App\Lib\BaseApi {

    /**
     * 签署代扣协议
	 * @param array $params
	 * [
	 *		'out_agreement_no' => '', //业务平台支付码
	 *		'channel_type'	=> '', //渠道类型
	 *		'name'			=> '', //签约名称
	 *		'back_url'		=> '', //后端回调地址
	 *		'front_url'		=> '', //前端回调地址
	 *		'user_id'		=> '', //用户id
	 * ]
	 * @return array 
	 * [
	 *		'url'		=> '',	//	url地址
	 *		'params'	=> '',	//	参数
	 * ]
	 * @throws \Exception			请求失败时抛出异常
	 */
	public static function getSignUrl( array $params ){
		return self::request(\env('PAY_APPID'), \env('PAY_API'),'pay.withhold.agreement.url', '1.0', $params);
	}

    /**
     * 查询代扣协议
	 * @param array $params
	 * [
	 *		'agreement_no'		=> '', //【必选】string 支付系统签约编号
	 *		'out_agreement_no'	=> '', //【必选】string 业务系统签约编号
	 * ]
	 * @return array 
	 * [
	 *		'agreement_no'		=> '', //【必选】string 支付系统签约编号
	 *		'out_agreement_no'	=> '', //【必选】string 业务系统签约编号
	 *		'status'			=> '', //【必选】string 状态；init：初始化；signed：已签约；unsigned：已解约
	 * ]
	 * @throws \Exception			请求失败时抛出异常
	 */
	public static function queryAgreement( array $params ){
		return self::request(\env('PAY_APPID'), \env('PAY_API'),'pay.api.withholdingstatus', '1.0', $params);
	}
	
    /**
     * 代扣 扣款接口
     * @param array $params
     * [
     *		'agreement_no'	=> '', //支付平台代扣协议号
     *		'out_trade_no'	=> '', //业务系统授权码
     *		'amount'		=> '', //交易金额；单位：分
     *		'back_url'		=> '', //后台通知地址
     *		'name'			=> '', //交易名称
     *		'user_id'		=> '', //业务平台用户id
     * ]
     * @return array
     * [
     *		'trade_no'			=> '',//支付系统交易码
     *		'out_trade_no'		=> '',//业务系统交易码
     *		'out_agreement_no'	=> '',//业务系统代扣协议号
     *		'status'			=> '',//状态：processing：申请成功，处理中；其它情况失败
     * ]
	 * @throws \Exception			请求失败时抛出异常
     */
    public static function deduct( array $params ){
		return self::request(\env('PAY_APPID'), \env('PAY_API'), 'pay.withhold.deduct.applay', '1.0', $params);
    }

    /**
     * 代扣 扣款交易查询
     * @param array $params
     * [
     *		'trade_no'		=> '', //支付系统交易码
     *		'out_trade_no'	=> '', //支付系统交易码
     *		'user_id'		=> '', //用户id
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'trade_no'	=> '',	//支付系统交易码
     *		'out_trade_no'		=> '',	//业务系统交易码
     *		'status'		=> '',	//状态：processing：交易处理中；success：交易成功；failed：交易失败
     *		'amount'		=> '',	//交易金额；单位：分
     *		'trade_time'	=> '',	//交易时间戳
     *		'user_id'		=> '',	//用户id
     *		'out_agreement_no' => '',//支付平台协议号
     * ]
	 * @throws \Exception			请求失败时抛出异常
     */
    public static function deductQuery( array $params ){
		return self::request(\env('PAY_APPID'), \env('PAY_API'), 'pay.withhold.deduct.query', '1.0', $params);
    }


    /**
     * 代扣 解约申请
     * @param array $params
     * [
     *		'user_id'		=> '', //租机平台用户ID
     *		'agreement_no'	=> '', //支付平台签约协议号
     *		'back_url'		=> '', //后端回调地址
     * ]
     * @return array
     * [
     *		'user_id'			=> '', //用户id
     *		'out_agreement_no'	=> '', //支付平台签约协议号
     *		'alipay_user_id'	=> '', //用户支付宝id（2088开头）
     *		'status'			=> '', //申请状态：processing：申请成功处理中；其它情况失败
     * ]
	 * @throws \Exception			请求失败时抛出异常
     */
    public static function unSign( array $params ){
		return self::request(\env('PAY_APPID'), \env('PAY_API'), 'pay.withhold.agreement.unsign', '1.0', $params);
    }
}