<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 *
 * 支付宝预授权接口
 * @author zjh
 */
class FundAuthApi extends \App\Lib\BaseApi {

    /**
     * 预授权获取URL接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'out_auth_no' => '', //订单系统授权码
     *		'amount' => '', //授权金额；单位：分
     *		'front_url' => '', //前端回跳地址
     *		'back_url' => '', //后台通知地址
     *		'name' => '', //预授权名称
     *		'user_id' => '', //用户ID
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'Authorization_url' => '',跳转预授权接口
     * ]
     */
    public static function fundauthUrl( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.fundauth');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '获取预授权链接地址失败';
            return false;
        }
        return $Response->getData();
    }

    /**
     * 预授权状态查询接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'auth_no' => '', //支付系统授权码
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_auth_no' => '',//支付系统授权码
     *		'auth_no' => '',//订单系统授权码
     *		'total_freeze_amount' => '',//累计授权金额；单位：分
     *		'total_unfreeze_amount' => '',//累计解冻金额；单位：分
     *		'total_pay_amount' => '',//累计转支付金额；单位：分
     *		'status' => '',//状态；0：已取消；1：授权处理中；2：授权完成；3：关闭；4：完成
     *		'auth_time' => '',//授权完成时间
     * ]
     */
    public static function authorizationStatus( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.authorizationstatus');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '查询预授权状态失败';
            return false;
        }
        return $Response->getData();
    }

    /**
     * 预授权解冻接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'out_trade_no' => '', //订单系统交易码
     *		'auth_no' => '', //支付系统授权码
     *		'amount' => '', //解冻金额 单位：分
     *		'back_url' => '', //后台通知地址
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'amount' => '',//解冻金额 单位：分
     * ]
     */
    public static function thaw( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.thaw');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '解冻预授权金额失败';
            return false;
        }
        return $Response->getData();
    }

    /**
     * 预授权转支付接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'out_trade_no' => '', //业务系统授权码
     *		'auth_no' => '', //支付系统授权码
     *		'amount' => '', //交易金额；单位：分
     *		'back_url' => '', //后台通知地址
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'amount' => '',//交易金额；单位：分
     *		'create_time' => '',//创建时间戳
     * ]
     */
    public static function thawPay( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.thawpay');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝预授权转支付失败';
            return false;
        }
        return $Response->getData();
    }
}