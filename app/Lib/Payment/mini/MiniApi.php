<?php
namespace App\Lib\Payment\mini;

/**
 * 支付宝芝麻小程序(代扣) 发送请求
 *
 * @author zhangjinhui
 */
class MiniApi {
    //取消
    private $CANCEL = 'CANCEL';
    //完结
    private $FINISH = 'FINISH';
    //分期扣款
    private $INSTALLMENT = 'INSTALLMENT';

    private $error = '';
    /*
     * 错误信息
     */
    public function getError( ){
        return $this->error;
    }
    /*
     * 订单关闭（代扣）发送请求
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     *      'out_trans_no'=>'',//资金交易号
     *      'pay_amount'=>'',//支付金额
     *      'remark'=>'',//订单操作说明
     *      'app_id'=>'',//芝麻小程序APPID
     * ]
     */
    public function withhold( $params ){
        $params['order_operate_type'] = $this->INSTALLMENT;
        $CommonMiniApi = new \App\Lib\Payment\mini\sdk\CommonMiniApi($params['app_id']);
        $b = $CommonMiniApi->withholdingCancelClose($params);
        if($b == false){
            $this->error = $CommonMiniApi->getError();
            return false;
        }
        $result = $CommonMiniApi->getResult();
        \App\Lib\Common\LogApi::error('发送扣款请求',['request'=>$params,'response'=>$result]);
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
    public function OrderClose( $params ){
        $CommonMiniApi = new \App\Lib\Payment\mini\sdk\CommonMiniApi($params['app_id']);
        $params['order_operate_type'] = $this->FINISH;
        $b = $CommonMiniApi->withholdingCancelClose($params);
        if($b === false){
            $this->error = $CommonMiniApi->getError();
            return false;
        }
        $result = $CommonMiniApi->getResult();
        \App\Lib\Common\LogApi::error('发送关闭订单请求',['request'=>$params,'response'=>$result]);
        //返回
        return $result;
    }


    /*
     * 订单取消
     * params [
     *      'out_order_no'=>'',//商户端订单号
     *      'zm_order_no'=>'',//芝麻订单号
     *      'app_id'=>'',//芝麻小程序APPID
     * ]
     */
    public function OrderCancel( $params ){
        $params['order_operate_type'] = $this->CANCEL;
        $CommonMiniApi = new \App\Lib\Payment\mini\sdk\CommonMiniApi($params['app_id']);
        $b = $CommonMiniApi->withholdingCancelClose($params);
        if($b === false){
            $this->error = $CommonMiniApi->getError();
            return false;
        }
        $result = $CommonMiniApi->getResult();
        \App\Lib\Common\LogApi::error('发送取消订单请求',['request'=>$params,'response'=>$result]);
        return $result;
    }



}