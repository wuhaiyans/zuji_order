<?php

namespace App\Lib;

/**
 * api 状态码
 * Class ApiStatus
 * 1xxxx： 公用
 * 2xxxx： 无用
 * 3xxxx： 订单
 * 4xxxx： 收发货
 * 5xxxx： 支付
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
    /**
     * @var string 状态码：支付错误
     */
    const CODE_50004 = '50004';

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


    const CODE_31201= '32201'; //[订单清算详情]

    //-+----------------------------------------------------------------------
    // | 退货退款错误信息
    //-+----------------------------------------------------------------------
    const CODE_33001 = '33001'; //[退换货]退货单号不能为空
    const CODE_33002 = '33002'; //[退换货]退货单号错误
    const CODE_33003 = '33003'; //[退换货]订单编号不能为空
    const CODE_33004 = '33004'; //[退换货]审核状态不能为空
    const CODE_33005 = '33005'; //[退换货]审核备注信息不能为空
    const CODE_33007 = '33007'; //[退换货]修改订单状态失败
    const CODE_33008 = '33008'; //[退换货]修改退换货状态失败
    const CODE_33009 = '33009'; //[退换货]修改商品状态失败
    const CODE_34001 = '34001'; //[退换货]程序异常
    const CODE_34002 = '34002'; //[退换货]无退货单信息
    const CODE_34003 = '34003'; //[退换货]创建收货单失败
    const CODE_34004 = '34004'; //[退换货]退货状态不能为空
    const CODE_34005 = '34005'; //[退换货]未找到此订单
    const CODE_34006 = '34006'; //[退换货]不允许取消
    const CODE_34007 = '34007'; //[退换货]创建退货单失败
    const CODE_34008 = '34008'; //[退换货]创建退款清单失败
    const CODE_34009 = '34009'; //[退换货]创建换货单失败

    //-+----------------------------------------------------------------------
    // | 订单系统支付阶段错误信息
    //-+----------------------------------------------------------------------
    const CODE_30900 = '30900'; //[支付阶段]请求失败
	const CODE_30901 = '30901';	// 支付环节链接地址创建失败
	
    //-+----------------------------------------------------------------------
    // | 收货错误信息
    //-+----------------------------------------------------------------------
    const CODE_40001 = '40001'; //[收货]订单号不可为空
    const CODE_40002 = '40002'; //[收货]设备序号不可为空
    const CODE_40003 = '40003'; //[收货]单号不可为空
    const CODE_40004 = '40004'; //[收货]物流信息错误
    const CODE_40005 = '40005'; //[收货]创建收货单失败
    const CODE_40006 = '40006'; //[收货]获取数据失败
    const CODE_40007 = '40007'; //[收货]更新数据失败

    //-+----------------------------------------------------------------------
    // | 发货错误信息
    //-+----------------------------------------------------------------------
    const CODE_41001 = '41001'; //[发货]订单号不可为空
    const CODE_41002 = '41002'; //[发货]设备序号不可为空
    const CODE_41003 = '41003'; //[发货]单号不可为空
    const CODE_41004 = '41004'; //[发货]物流信息错误
    const CODE_41005 = '41005'; //[发货]创建发货单失败
    const CODE_41006 = '41006'; //[发货]获取数据失败
    const CODE_41007 = '41007'; //[发货]更新数据失败






    //-+----------------------------------------------------------------------
    // | 分期代扣
    //-+----------------------------------------------------------------------
    const CODE_71000 = '71000'; //不允许扣款
    const CODE_71001 = '71001'; //数据异常
    const CODE_71002 = '71002'; //租机交易码错误
    const CODE_71003 = '71003'; //代扣金额错误
    const CODE_71004 = '71004'; //用户代扣协议错误
    const CODE_71005 = '71005'; //买家余额不足
    const CODE_71006 = '71006'; //扣款失败
    const CODE_71007 = '71007'; //查询用户代扣协议出现异常 获取签约代扣URL地址失败
    const CODE_71008 = '71008'; //获取签约代扣URL地址失败
    const CODE_71009 = '71009'; //获取用户支付宝id失败
    const CODE_71010 = '71010'; //不允许解除代扣

    //-+----------------------------------------------------------------------
    // | 预授权
    //-+----------------------------------------------------------------------
    const CODE_81000 = '81000'; //获取预授权URL地址错误
	
	
    //-+----------------------------------------------------------------------
    // |【还机业务】
	// |	[91:参数错误]
	// |	[92:过程错误]
	// |		[921:过程入库参数错误]
	// |		[922:过程入库结果错误]
	// |		[923:过程读库参数错误]
	// |		[924:过程读库结果错误]
	// |		[925:过程操作禁止错误]
	// |		[926:过程改库参数错误]
	// |		[927:过程改库结果错误]
	// |	[93:接口错误]
	// |		[931:过程接口调用参数错误]
	// |		[932:过程接口调用结果错误]
	// |	[94:异常错误]
    //-+----------------------------------------------------------------------
	/**
	 * [还机][参数错误]必要参数缺失!
	 */
    const CODE_91000 = '91000';
	/**
	 * [还机][参数错误]商品编号不能为空!
	 */
    const CODE_91001 = '91001';
	/**
	 * [还机][入库结果参数]入库参数错误!
	 */
    const CODE_92100 = '92100';
	/**
	 * [还机][入库结果错误]入库结果错误!
	 */
    const CODE_92200 = '92200';
	/**
	 * [还机][入库错误]还机单创建失败!
	 */
    const CODE_92201 = '92201';
	
	/**
	 * [还机][读库参数错误]读库参数错误!
	 */
    const CODE_92300 = '92300';
	
	/**
	 * [还机][读库参数错误]获取商品信息：商品编号参数为空!
	 */
    const CODE_92301 = '92301';
	/**
	 * [还机][读库结果错误]读库结果错误!
	 */
    const CODE_92400 = '92400';
	/**
	 * [还机][读库结果错误]商品数据读取为空!
	 */
    const CODE_92401 = '92401';
	/**
	 * [还机][读库结果错误]还机单数据读取为空!
	 */
    const CODE_92402 = '92402';
	/**
	 * [还机][操作禁止]操作禁止!
	 */
    const CODE_92500 = '92500';
	/**
	 * [还机][改库参数错误]改库参数错误!
	 */
    const CODE_92600 = '92600';
	/**
	 * [还机][改库结果错误]改库结果错误!
	 */
    const CODE_92700 = '92700';
	/**
	 * [还机][改库结果错误]还机单更新出错!
	 */
    const CODE_92701 = '92701';
	/**
	 * [还机][程序异常]程序异常!
	 */
    const CODE_94000 = '94000';

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
        self::CODE_60002 => '第三方报错',

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
        self::CODE_33007 => '[退换货]修改订单状态失败',
        self::CODE_33008 => '[退换货]修改退换货状态失败',
        self::CODE_33009 => '[退换货]修改商品状态失败',
        self::CODE_34001 => '[退换货]程序异常',
        self::CODE_34002 => '[退换货]无退货单信息',
        self::CODE_34003 =>'[退换货]创建收货单失败',
        self::CODE_34004 =>'[退换货]退货状态不能为空',
        self::CODE_34005 =>'[退换货]未找到此订单',
        self::CODE_34006 =>'[退换货]不允许取消',
        self::CODE_34007 =>'[退换货]创建退货单失败',
        self::CODE_34008 =>'[退换货]创建退款清单失败',
        self::CODE_34009 =>'[退换货]创建换货单失败',



        //收货
        self::CODE_40001 => '[收货]订单号不可为空',
        self::CODE_40002 => '[收货]设备序号不可为空',
        self::CODE_40003 => '[收货]单号不可为空',
        self::CODE_40004 => '[收货]物流信息错误',
        self::CODE_40005 => '[收货]创建收货单失败',
        self::CODE_40006 => '[收货]获取数据失败',
        self::CODE_40007 => '[收货]更新数据失败',

        //发货
        self::CODE_41001 => '[发货]订单号不可为空',
        self::CODE_41002 => '[发货]设备序号不可为空',
        self::CODE_41003 => '[发货]单号不可为空',
        self::CODE_41004 => '[发货]物流信息错误',
        self::CODE_41005 => '[发货]创建发货单失败',
        self::CODE_41006 => '[发货]获取数据失败',
        self::CODE_41007 => '[发货]更新数据失败',


        self::CODE_50010 => '优惠券不可用',
        self::CODE_50004 => '支付错误',

        // 分期代扣
        self::CODE_71000 => '不允许扣款',
        self::CODE_71001 => '分期数据异常',
        self::CODE_71002 => '租机交易码错误',
        self::CODE_71003 => '代扣金额错误',
        self::CODE_71004 => '用户代扣协议错误',
        self::CODE_71005 => '买家余额不足',
        self::CODE_71006 => '扣款失败',
        self::CODE_71007 => '查询用户代扣协议出现异常',
        self::CODE_71008 => '获取签约代扣URL地址失败',
        self::CODE_71009 => '获取用户支付宝id失败',
        self::CODE_71010 => '不允许解除代扣',

        // 预授权
        self::CODE_81000 => '获取预授权URL地址错误',
		
		//还机
		self::CODE_91000 => '[还机][参数错误]必要参数缺失!',
		self::CODE_91001 => '[还机][参数错误]商品编号不能为空!',
		self::CODE_92100 => '[还机][入库参数错误]入库参数错误!',
		self::CODE_92200 => '[还机][入库结果错误]入库结果错误!',
		self::CODE_92201 => '[还机][入库结果错误]还机单创建失败!',
		self::CODE_92300 => '[还机][读库参数错误]读库参数错误!',
		self::CODE_92301 => '[还机][读库参数错误]获取商品信息：商品编号参数为空!',
		self::CODE_92400 => '[还机][读库结果错误]读库结果错误!',
		self::CODE_92401 => '[还机][读库结果错误]商品数据读取为空!',
		self::CODE_92402 => '[还机][读库结果错误]还机单数据读取为空!',
		self::CODE_92500 => '[还机][操作禁止]操作禁止!',
		self::CODE_92600 => '[还机][改库参数错误]改库参数错误!',
		self::CODE_92700 => '[还机][改库结果错误]改库结果错误!',
		self::CODE_92701 => '[还机][改库结果错误]还机单更新出错!',
		self::CODE_94000 => '[还机][程序异常]程序异常!',
		
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
		if( $this->msg == '' && isset(self::$errCodes[$this->code]) ){
			return self::$errCodes[$this->code];
		}
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
