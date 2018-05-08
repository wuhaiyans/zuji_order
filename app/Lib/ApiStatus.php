<?php

namespace App\Lib;

/**
 * api 状态码
 * Class ApiStatus
 */
class ApiStatus {

    const CODE_0 = '0';         // 成功
    //-+----------------------------------------------------------------------
    // | 接口协议级别 请求 错误
    //-+----------------------------------------------------------------------
    const CODE_10100 = '10100';//空请求
    const CODE_10101 = '10101';//格式错误
    const CODE_10102 = '10102';//channel_id 错误
    const CODE_10103 = '10103';//method 错误
    const CODE_10104 = '10104';//params	 错误



    //-+----------------------------------------------------------------------
    // | 业务参数错误
    //-+----------------------------------------------------------------------
    const CODE_20001 = '20001';// 参数必须，或参数值错误

    //-+----------------------------------------------------------------------
    // | 内部服务异常错误
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：程序异常（程序未捕获的异常：程序发生致命错误）
     */
    const CODE_50000 = '50000'; //
    const CODE_50010 = '50010'; //优惠券错误

    //-+----------------------------------------------------------------------
    // | 依赖接口错误
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：依赖接口错误（调用第三方接口时失败）
     */
    const CODE_60000 = '60000'; //
    const CODE_60001 = '60001'; //数据未获取成功
    const CODE_60002 = '60002'; //第三方报错

    //-+----------------------------------------------------------------------
    // | 下单错误信息
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：下单异常状态
     */
    const CODE_30000 = '30000'; //[下单][代扣组件]未签约代扣协议
    const CODE_30001 = '30001'; //[下单][代扣组件]用户已经解约代扣协议
    const CODE_30002 = '30002'; //[下单][渠道]appid已被禁用
    const CODE_30003 = '30003'; //[下单][渠道]渠道已被禁用
    const CODE_30004 = '30004'; //[下单][渠道]商品渠道错误
    const CODE_30005 = '30005'; //下单失败
    const CODE_30006 = '30006'; //分数过低

    const CODE_31001 = '31001'; //[取消订单]订单号不能为空
    const CODE_31002 = '31002'; //[取消订单]修改订单状态失败
    const CODE_31003 = '31003'; //[取消订单]修改商品库存失败
    const CODE_31004 = '31004'; //[取消订单]还券失败
    const CODE_31005 = '31005'; //[取消订单]关闭分期失败
    const CODE_31006 = '31006'; //[取消订单]失败


    const CODE_32001 = '32001'; //[获取订单详情]订单号不存在
    const CODE_32002 = '32002'; //[获取订单详情]数据异常
    //-+----------------------------------------------------------------------
    // | 退货退款错误信息
    //-+----------------------------------------------------------------------
    const CODE_33001 = '33001'; //[退换货]退货单号不能为空
    const CODE_33002 = '33002'; //[退换货]退货单号错误
    const CODE_33003 = '33003'; //[退换货]订单编号不能为空
    const CODE_33004 = '33004'; //[退换货]审核状态不能为空
    const CODE_33005 = '33005'; //[退换货]审核备注信息不能为空
    const CODE_33006 = '33006'; //[退换货]修改退换货状态失败
    const CODE_33007 = '33007'; //[退换货]修改订单状态失败
    const CODE_33008 = '33008'; //[退换货]修改退换货状态失败
    //-+----------------------------------------------------------------------
    // | 商品错误信息
    //-+----------------------------------------------------------------------
    const CODE_40000 = '40000'; //商品信息错误
    const CODE_40001 = '40001'; //商品库存不足
    const CODE_40002 = '40002';//押金错误
    //-+----------------------------------------------------------------------
    // | 用户错误信息
    //-+----------------------------------------------------------------------
    const CODE_41000 = '41000'; //账号锁定
    const CODE_41001 = '41001'; //退款次数过多，暂时无法下单,
    const CODE_41002 = '41002'; //账户尚未信用认证,
    const CODE_41003 = '41003'; //信用认证过期,
    const CODE_41004 = '41004'; //有未完成订单,
    const CODE_41005 = '41005'; //用户地址错误,


    public static $errCodes = [
        self::CODE_0     => 'success',
        self::CODE_10100 => '空请求',
        self::CODE_10102 => '请求格式错误',
        self::CODE_10103 => '[method]错误',
        self::CODE_10104 => '[params]错误',

        self::CODE_20001 =>  '参数必须',
        self::CODE_50000 => '程序异常',
        self::CODE_60000 => '第三方接口调用出错',
        self::CODE_60001 => '第三方数据未获取成功',
        self::Code_60002 => '第三方报错',

        //下单返回状态信息
        self::CODE_30000 => '[下单][代扣]未签约代扣协议',
        self::CODE_30001 => '[下单][代扣]用户已经解约代扣协议',
        self::CODE_30002 => '[下单][渠道]appid已被禁用',
        self::CODE_30003 => '[下单][渠道]渠道已被禁用',
        self::CODE_30004 => '[下单][渠道]商品渠道错误',
        self::CODE_30005 => '下单失败',
        self::CODE_30006 => '分数过低',



        //取消订单返回信息
        self::CODE_31001 => '[取消订单]订单号不能为空',
        self::CODE_31002 => '[取消订单]修改订单状态失败',
        self::CODE_31003 => '[取消订单]修改商品库存失败',
        self::CODE_31004 => '[取消订单]还券失败',
        self::CODE_31005 => '[取消订单]关闭分期失败',
        self::CODE_31006 => '[取消订单]失败',

        //获取订单
        self::CODE_32001 => '[获取订单详情]订单号不存在',
        self::CODE_32002 => '[获取订单详情]数据异常',

        //退换货信息
        self::CODE_33001 => '[退换货]退货单号不能为空',
        self::CODE_33002 => '[退换货]退货单号错误',
        self::CODE_33003 => '[退换货]订单编号不能为空',
        self::CODE_33004 => '[退换货]审核状态不能为空',
        self::CODE_33005 => '[退换货]审核备注信息不能为空',
        self::CODE_33006 => '[退换货]修改退换货状态失败',
        self::CODE_33007 => '[退换货]修改订单状态失败',
        self::CODE_33008 => '[退换货]修改退换货状态失败',
        //商品错误信息
        self::CODE_40000 => '商品信息错误',
        self::CODE_40001 => '商品库存不足',
        self::CODE_40002 => '押金错误',

        //用户错误信息
        self::CODE_41000 => '账号锁定',
        self::CODE_41001 => '退款次数过多，暂时无法下单',

        self::CODE_41002 => '账户尚未信用认证',
        self::CODE_41003 => '信用认证过期',
        self::CODE_41004 => '有未完成订单',
        self::CODE_41005 => '用户地址错误',
        self::CODE_50010 => '优惠券不可用',

    ];
	

    private $code = '';
    private $msg = '';
    
    public function __construct($code=self::CODE_0, $msg='') {
        $this->code = $code;
        $this->msg = $msg;
    }
    
    public function isSuccessed(){
        return $this->code === self::CODE_0;
    }

    public function getCode() {
        return $this->code;
    }

    public function getMsg() {
        return $this->msg;
    }

    /**
     * 设置成功
     * @return \app\common\Status
     */
    public function success() {
        $this->setCode(self::CODE_0);
        return $this;
    }

    public function setCode($code) {
        $this->code = $code;
        return $this;
    }

    public function setMsg($msg) {
        $this->msg = $msg;
        return $this;
    }


}
