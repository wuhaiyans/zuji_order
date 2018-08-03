<?php
namespace App\Lib\Payment\mini;

use Illuminate\Support\Facades\Redis;
/**
 * 支付宝芝麻小程序(代扣) 发送请求
 *
 * @author zhangjinhui
 */
class MiniApi {
    //取消
    private static $CANCEL = 'CANCEL';
    //完结
    private static $FINISH = 'FINISH';
    //分期扣款
    private static $INSTALLMENT = 'INSTALLMENT';

    private static $error = '';
    /*
     * 错误信息
     */
    public static function getError( ){
        return self::$error;
    }
    /*
     * 订单代扣发送请求
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     *      'out_trans_no'=>'',//资金交易号
     *      'pay_amount'=>'',//支付金额
     *      'remark'=>'',//订单操作说明
     *      'app_id'=>'',//芝麻小程序APPID
     * ]
     */
    public static function withhold( $params ){
        $params['order_operate_type'] = self::$INSTALLMENT;
        $CommonMiniApi = new \App\Lib\AlipaySdk\sdk\CommonMiniApi($params['app_id']);
        $b = $CommonMiniApi->withholdingCancelClose($params);
        if($b == false){
            self::$error = $CommonMiniApi->getError();
            return false;
        }
        $result = $CommonMiniApi->getResult();
        \App\Lib\Common\LogApi::error('发送扣款请求',['request'=>$params,'response'=>$result]);
        //加redis订单扣款标示
        Redis::set('zuji:order:miniorder:orderno:'.$params['out_order_no'], 'MiniWithhold');
        //返回字符串
        return $result['zhima_merchant_order_credit_pay_response']['pay_status'];
    }

    /*
     * 订单关闭 发送请求
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     *      'out_trans_no'=>'',//商户端交易号
     *      'pay_amount'=>'',//支付金额
     *      'remark'=>'',//订单操作说明
     *      'app_id'=>'',//芝麻小程序APPID
     * ]
     */
    public static function OrderClose( $params ){
        $CommonMiniApi = new \App\Lib\AlipaySdk\sdk\CommonMiniApi($params['app_id']);
        $params['order_operate_type'] = self::$FINISH;
        $b = $CommonMiniApi->withholdingCancelClose($params);
        if($b === false){
            self::$error = $CommonMiniApi->getError();
            return false;
        }
        $result = $CommonMiniApi->getResult();
        \App\Lib\Common\LogApi::error('发送关闭订单请求',['request'=>$params,'response'=>$result]);
        //加redis订单完成标示
        Redis::set('zuji:order:miniorder:orderno:'.$params['out_order_no'], 'MiniOrderClose');
        //返回
        return $result;
    }


    /*
     * 订单取消
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     *      'remark' => '',//订单操作说明'
     *      'app_id'=>'',//芝麻小程序APPID
     * ]
     */
    public static function OrderCancel( $params ){
        $params['order_operate_type'] = self::$CANCEL;
        $CommonMiniApi = new \App\Lib\AlipaySdk\sdk\CommonMiniApi($params['app_id']);
        $b = $CommonMiniApi->withholdingCancelClose($params);
        if($b === false){
            self::$error = $CommonMiniApi->getError();
            return false;
        }
        $result = $CommonMiniApi->getResult();
        \App\Lib\Common\LogApi::error('发送取消订单请求',['request'=>$params,'response'=>$result]);
        return $result;
    }



}