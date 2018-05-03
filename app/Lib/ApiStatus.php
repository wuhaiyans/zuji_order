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

    //-+----------------------------------------------------------------------
    // | 依赖接口错误
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：依赖接口错误（调用第三方接口时失败）
     */
    const CODE_60000 = '60000'; //
    const CODE_60001 = '60001'; //数据未获取成功
    const Code_60002 = '60002'; //第三方报错

    //-+----------------------------------------------------------------------
    // | 下单错误信息
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：下单异常状态
     */
    const CODE_30000 = '30000'; //[下单][代扣组件]未签约代扣协议
    const CODE_30001 = '30001'; //[下单][代扣组件]用户已经解约代扣协议



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
        self::CODE_30000 =>  '[下单][代扣组件]未签约代扣协议',
        self::CODE_30001 => '[下单][代扣组件]用户已经解约代扣协议',

    ];
}
