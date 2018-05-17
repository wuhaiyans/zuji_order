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
     * 代扣 签约（获取签约地址）
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'user_id' => '', //租机平台用户ID
     *		'out_agreement_no' => '', //业务平台签约协议号
     *		'front_url' => '', //前端回跳地址
     *		'back_url' => '', //后台通知地址
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'withholding_url' => '',//签约跳转url地址
     * ]
     */
    public static function withholdingUrl( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.withholdingurl');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝代扣 获取url地址接口';
            return false;
        }
        return $Response->getData();
    }

    /**
     * 代扣 扣款接口
     * @param string $appid		应用ID
     * @param array $params
     * [
     *		'out_trade_no' => '', //业务系统授权码
     *		'amount' => '', //交易金额；单位：分
     *		'back_url' => '', //后台通知地址
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'create_time' => '',//创建时间戳
     * ]
     */
    public static function withhold( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.withhold');
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
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'out_trade_no' => '',//支付系统交易码
     *		'trade_no' => '',//业务系统交易码
     *		'status' => '',//状态：0：已取消；1：交易处理中；2：交易成功；3：交易失败
     *		'amount' => '',//交易金额；单位：分
     *		'trade_time' => '',//交易时间戳
     * ]
     */
    public static function withholdquery( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.withholdquery');
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
     *		'alipay_user_id' => '', //支付宝用户id（2088开头）
     *		'user_id' => '', //租机平台用户id
     *		'agreement_no' => '', //签约协议号
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'status' => '',//状态：Y：已签约；N：未签约
     *		'agreement_no' => '',//协议号
     *		'sign_time' => '',//签署时间
     * ]
     */
    public static function withholdingstatus( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.withholdingstatus');
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
     *		'agreement_no' => '', //签约协议号
     * ]
     * @return mixed false：失败；array：成功
     * [
     *		'user_id' => '',//用户id
     *		'alipay_user_id' => '',//用户支付宝id（2088开头）
     *		'status' => '',//解约是否成功 0：成功1：失败
     * ]
     */
    public static function rescind( $appid,array $params ){
        $ApiRequest = new ApiRequest();
        $ApiRequest->setUrl(env('PAY_TERRACE_URL'));
        $ApiRequest->setAppid( $appid );	// 业务应用ID
        $ApiRequest->setMethod('pay.alipay.rescind');
        $ApiRequest->setParams($params);
        $Response = $ApiRequest->send();
        if( !$Response->isSuccessed() ){
            self::$error = '支付宝代扣解约失败';
            return false;
        }
        return $Response->getData();
    }
}