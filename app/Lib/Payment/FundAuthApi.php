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
     * 预授权状态查询接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'auth_no' => '', //支付系统授权码
     *		'user_id' => '',//用户id
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_auth_no' => '',//支付系统授权码
     *		'auth_no' => '',//订单系统授权码
     *		'total_freeze_amount' => '',//累计授权金额；单位：分
     *		'total_unfreeze_amount' => '',//累计解冻金额；单位：分
     *		'total_pay_amount' => '',//累计转支付金额；单位：分
     *		'status' => '',//状态；0：已取消；1：授权处理中；2：授权完成；3：关闭；4：完成
     *		'user_id' => '',//用户id
     *		'auth_time' => '',//授权完成时间
     * ]
     */
    public static function fundAuthStatus( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.fundauthstatus');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '查询预授权状态失败';
            return false;
        }
        return $Response->getData();
    }

    /**
     * 查询授权解冻 转支付 状态接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'trade_no' => '', //支付系统授权码
     *		'user_id' => '', //支付系统授权码
     *		'type' => '', //支付系统授权码
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//订单系统交易码
     *		'out_auth_no' => '',//支付系统授权码
     *		'amount' => '',//交易金额；单位：分
     *		'status' => '',//状态；0：初始化；1：授权完成；2：授权失败；3：关闭；4：完成
     *		'user_id' => '',//用户id
     *		'trade_time' => '',//交易成功时间戳
     *		'type' => '',//请求类型 1:转支付 ;2:解冻 ;
     * ]
     */
    public static function unfreezeAndPayStatus( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.unfreezeandpaystatus');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '查询授权解冻 转支付 状态失败';
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
     *		'user_id' => '', //用户id
     *		'remark' => '', //业务描述
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'out_auth_no' => '',//支付系统授权码
     * ]
     */
    public static function unfreeze( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.unfreeze');
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
     *		'user_id' => '', //用户id
     *		'remark' => '', //业务描述
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'out_auth_no' => '',//支付系统授权码
     * ]
     */
    public static function unfreezeAndPay( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.unfreezeandpay');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝预授权转支付失败';
            return false;
        }
        return $Response->getData();
    }
}