<?php

/**
 * 接口 状态类
 * @access public
 * @author Liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 */
class ApiStatus {
    /**
     * 状态码：成功
     */
    const CODE_0 = '0';         // 成功
    
    //-+----------------------------------------------------------------------
    // | 接口协议级别 请求 错误
    //-+----------------------------------------------------------------------
    const CODE_10100 = '10100';//空请求
    const CODE_10101 = '10101';//格式错误
    const CODE_10102 = '10102';//appid 错误
    const CODE_10103 = '10103';//method 错误
    const CODE_10104 = '10104';//timestamp 错误
    const CODE_10105 = '10105';//version 错误
    const CODE_10106 = '10106';//params	 错误
    const CODE_10107 = '10107';//sign_type 错误	
    const CODE_10108 = '10108';//sign 错误
    const CODE_10109 = '10109';//签名验证失败
    //
    //-+----------------------------------------------------------------------
    // | 接口协议级别 响应 错误
    //-+----------------------------------------------------------------------
    /**
     * 状态码：响应协议错误
     */
    const CODE_10000 = '10100';// 空响应
    /**
     * 状态码：数据格式错误，暂时只支持json
     */
    const CODE_10001 = '10001';//格式错误
    const CODE_10002 = '10002';//code
    const CODE_10003 = '10003';//sub_code
    const CODE_10004 = '10004';//data	
    const CODE_10005 = '10005';// sign不存在
    const CODE_10006 = '10006';// 签名错误
    
    //-+----------------------------------------------------------------------
    // | 业务参数错误
    //-+----------------------------------------------------------------------
    const CODE_20000 = '20000';// 业务参数获取失败
    const CODE_20001 = '20001';// 参数必须，或参数值错误

    //-+----------------------------------------------------------------------
    // | 用户权限错误
    //-+----------------------------------------------------------------------
    /**
     * @var string 未登录或登录超时，要求用户去登录
     */
    const CODE_40001 = '40001';
    /**
     * @var string 用户无权操作（拒绝执行）
     */
    const CODE_40003 = '40003';
    /**
     * @var string 登录错误
     */
    const CODE_40004 = '40004';
    //-+----------------------------------------------------------------------
    // | 内部服务异常错误
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：程序异常（程序未捕获的异常：程序发生致命错误）
     */
    const CODE_50000 = '50000'; // 
    
    //-+----------------------------------------------------------------------
    // | 业务处理失败错误码
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：用户错误
     */
    const CODE_50001 = '50001';
    /**
     * @var string 状态码：商品错误
     */
    const CODE_50002 = '50002';
    /**
     * @var string 状态码：订单错误
     */
    const CODE_50003 = '50003';
    /**
     * @var string 状态码：支付错误
     */
    const CODE_50004 = '50004';
    /**
     * @var string 状态码：用户认证错误
     */
    const CODE_50005 = '50005';
    /**
     * @var string 状态码：退款错误（原路退回）
     */
    const CODE_50006 = '50006';
    /**
     * @var string 状态码：转账错误（账号转账）
     */
    const CODE_50007 = '50007';
    /**
     * @var string 状态码：内容错误（内容和推荐错误）
     */
    const CODE_50008 = '50008';
    /**
     * @var string 状态码：短信错误
     */
    const CODE_50009 = '50009';
    /**
     * @var string 状态码：优惠券错误
     */
    const CODE_50010 = '50010';
    /**
     * @var string 状态码：新机预租状态错误
     */
    const CODE_50011 = '50011';
    
    //-+----------------------------------------------------------------------
    // | 依赖接口错误
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：依赖接口错误（调用第三方接口时失败）
     */
    const CODE_60000 = '60000'; //
    
    
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
