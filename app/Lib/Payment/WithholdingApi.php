<?php
namespace App\Lib\Payment;
use App\Lib\ApiRequest;
/**
 *
 * 代扣接口
 * @author zjh
 */
class WithholdingApi extends \App\Lib\BaseApi {

    /**
     * 代扣 扣款接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'out_trade_no' => '', //业务系统授权码
     *		'amount' => '', //交易金额；单位：分
     *		'back_url' => '', //后台通知地址
     *		'name' => '', //交易备注
     *		'agreement_no' => '', //支付平台代扣协议号
     *		'user_id' => '', //业务平台用户id
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'out_agreement_no' => '',//支付平台代扣协议号
     * ]
     */
    public static function withholdingPay( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.withholdingpay');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝代扣扣款失败';
            return false;
        }
        return $Response->getData();
    }

    /**
     * 代扣 扣款交易查询
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'trade_no' => '', //支付系统交易码
     *		'user_id' => '', //用户id
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'status' => '',//状态：0：已取消；1：交易处理中；2：交易成功；3：交易失败
     *		'amount' => '',//交易金额；单位：分
     *		'trade_time' => '',//交易时间戳
     *		'user_id' => '',//用户id
     *		'out_agreement_no' => '',//支付平台协议号
     * ]
     */
    public static function withholdingPayQuery( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.withholdpayquery');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝代扣交易查询失败';
            return false;
        }
        return $Response->getData();
    }
    /**
     * 代扣 签约状态查询
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'user_id' => '', //租机平台用户id
     *		'agreement_no' => '', //支付平台签约协议号
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'status' => '',//状态：0：已签约；1：已解约 2：未签约
     *		'out_agreement_no' => '',//支付平台协议号
     *		'user_id' => '',//用户id
     *		'sign_time' => '',//签署时间
     * ]
     */
    public static function withholdingStatus( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.withholdingstatus');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝代扣签约查询失败';
            return false;
        }
        return $Response->getData();
    }
    /**
     * 代扣 签约完毕解约
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'user_id' => '', //租机平台用户ID
     *		'alipay_user_id' => '', //用户支付宝id（2088开头）
     *		'agreement_no' => '', //支付平台签约协议号
     *		'back_url' => '', //后端回调地址
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'user_id' => '',//用户id
     *		'out_agreement_no' => '',//支付平台签约协议号
     *		'alipay_user_id' => '',//用户支付宝id（2088开头）
     *		'status' => '',//解约是否成功 0：成功1：失败
     * ]
     */
    public static function unSign( array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_SYSTEM_URL'));
        $ApiRequest->setAppid( env('PAY_APP_ID') );	// 业务应用ID
        $ApiRequest->setMethod('pay.api.unsign');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝代扣解约失败';
            return false;
        }
        return $Response->getData();
    }
}