<?php
namespace App\Lib\Payment;

/**
 * 统一预授权接口
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class CommonFundAuthApi extends \App\Lib\BaseApi {

    /**
     * 预授权获取URL接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param array $params
     * [
     *		'out_fundauth_no'	=> '', //业务系统授权码 
     *		'amount'			=> '', //授权金额；单位：分
     *		'channel_type'		=> '', //授权渠道
     *		'front_url'			=> '', //前端回跳地址
     *		'back_url'			=> '', //后台通知地址
     *		'name'				=> '', //预授权名称
     *		'user_id'			=> '', //用户ID
     * ]
     * @return array	预授权地址信息
     * [
     *		'url'		=> '',	// 预授权地址
     *		'params'	=> '',	// 预授权地址
     * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function fundAuthUrl( array $params ){
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'), 'pay.fundauth.url', '1.0', $params);
    }

    /**
     * 预授权状态查询接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'fundauth_no'		=> '', //支付系统授权码
     *		'out_fundauth_no'	=> '', //业务系统授权码
     *		'user_id'			=> '', //用户id
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'fundauth_no'			=> '',//支付系统授权码
     *		'out_fundauth_no'		=> '',//业务系统授权码
     *		'total_freeze_amount'	=> '',//累计授权金额；单位：分
     *		'total_unfreeze_amount' => '',//累计解冻金额；单位：分
     *		'total_pay_amount'		=> '',//累计转支付金额；单位：分
     *		'status'				=> '',//状态；0：已取消；1：授权处理中；2：授权完成；3：关闭；4：完成
     *		'user_id'				=> '',//用户id
     *		'auth_time'				=> '',//授权完成时间
     * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function queryFundAuthStatus( array $params ){
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'), 'pay.fundauth.query', '1.0', $params);
    }

    /**
     * 查询授权解冻 转支付 状态接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param array $params
     * [
     *		'name'		=> '', //交易名称
     *		'trade_no'	=> '', //业务系统授权码
     *		'user_id'	=> '', //业务系统授权码
     *		'type'		=> '', //支付系统授权码
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'trade_no'		=> '',//支付系统交易码
     *		'out_trade_no'	=> '',//业务系统交易码
     *		'fundauth_no'	=> '',//支付系统授权码
     *		'amount'		=> '',//交易金额；单位：分
     *		'status'		=> '',//状态；0：初始化；1：授权完成；2：授权失败；3：关闭；4：完成
     *		'user_id'		=> '',//用户id
     *		'trade_time'	=> '',//交易成功时间戳
     *		'type'			=> '',//请求类型 1:转支付 ;2:解冻 ;
     * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function unfreezeAndPayStatus( array $params ){
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'), 'pay.api.unfreezeandpaystatus', '1.0', $params);
    }

    /**
     * 预授权解冻接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param array $params
     * [
     *		'name'			=> '', //交易名称
     *		'out_trade_no'	=> '', //业务系统交易码
     *		'fundauth_no'	=> '', //支付系统授权码
     *		'amount'		=> '', //解冻金额 单位：分
     *		'back_url'		=> '', //后台通知地址
     *		'user_id'		=> '', //用户id
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no'	=> '',//支付系统交易码
     *		'trade_no'		=> '',//业务系统交易码
     *		'out_auth_no'	=> '',//支付系统授权码
     * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function unfreeze( array $params ){
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'), 'pay.fundauth.unfreeze', '1.0', $params);
    }

    /**
     * 预授权转支付接口
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param array $params
     * [
     *		'name'			=> '', //交易名称
     *		'out_trade_no'	=> '', //业务系统授权码
     *		'fundauth_no'	=> '', //支付系统授权码
     *		'amount'		=> '', //交易金额；单位：分
     *		'back_url'		=> '', //后台通知地址
     *		'user_id'		=> '', //用户id
     *		'remark'		=> '', //业务描述
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'trade_no'		=> '',//支付系统交易码
     *		'out_trade_no'	=> '',//业务系统交易码
     *		'fundauth_no'	=> '',//支付系统授权码
     * ]
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function unfreezeAndPay( array $params ){
        //数据排序
        ksort($params);
        //生成秘钥
        $sign = \App\Lib\AlipaySdk\sdk\aop\AopClient::generateSignVal( http_build_query( $params ) );
        $params['sign'] = $sign;
        $params['sign_type'] = 'RSA';
		return self::request(\config('paysystem.PAY_APPID'), \config('paysystem.PAY_API'), 'pay.fundauth.topay.apply', '1.0', $params);
    }
}