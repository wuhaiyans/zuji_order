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
    const CODE_10102 = '10102';//app_id 错误
    const CODE_10103 = '10103';//method 错误
    const CODE_10104 = '10104';//params	 错误



    //-+----------------------------------------------------------------------
    // | 业务参数错误
    //-+----------------------------------------------------------------------
    const CODE_20001 = '20001';// 参数必须，或参数值错误
    //-+----------------------------------------------------------------------
    // | 检验token参数错误
    //-+----------------------------------------------------------------------
    const CODE_20002 = '20002';// 访问失败
    const CODE_20003 = '20003';// 验证失败

    //-+----------------------------------------------------------------------
    // | 内部服务异常错误
    //-+----------------------------------------------------------------------
    /**
     * @var string 状态码：程序异常（程序未捕获的异常：程序发生致命错误）
     */
    const CODE_50000 = '50000'; //
    const CODE_50001 = '50001'; //订单获取失败
    const CODE_50002 = '50002'; //订单商品获取失败
    const CODE_50003 = '50003'; //商品获取失败
    const CODE_50010 = '50010'; //优惠券错误
    /**
     * @var string 状态码：支付错误
     */
    const CODE_50004 = '50004';
    const CODE_50005 = '50005';

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
    const CODE_31007 = '31007'; //[取消订单]状态异常
    const CODE_31008 = '31008'; //[取消订单]解约代扣协议失败


    const CODE_32001 = '32001'; //[获取订单详情]订单号不存在
    const CODE_32002 = '32002'; //[获取订单详情]数据异常


    const CODE_30011 = '30011';//[客服]确认订单失败
    const CODE_30012 = '30012';//订单确认收货失败


    const CODE_30013 = '30013';//订单确认修改收货地址失败
    const CODE_30014 = '30014';//订单发货失败
    const CODE_30034 = '30034'; //[获取风控信息]数据异常
    const CODE_30036 = '30036'; //[获取出险详情信息]数据异常

    const CODE_31201= '31201'; //[订单清算详情]
    const CODE_31202= '31202'; //[订单清算操作失败]
    const CODE_31203= '31203'; //[订单清算取消失败]
    const CODE_31204= '31204'; //[订单清算操作成功]
    const CODE_31205= '31205';   //[订单清算不存在]

    //-+----------------------------------------------------------------------
    // | 退货退款错误信息
    //-+----------------------------------------------------------------------
    const CODE_33001 = '33001'; //[退换货]退换货审核失败
    const CODE_33002 = '33002'; //[退换货]退款审核失败
    const CODE_33003 = '33003'; //[退换货]物流单号上传失败
    const CODE_33004 = '33004'; //[退换货]取消退货申请失败
    const CODE_33005 = '33005'; //[退换货]退换货结果查看失败
    const CODE_33006 = '33006'; //[退换货]取消发货失败
    const CODE_33007 = '33007'; //[退换货]取消退款失败
    const CODE_33008 = '33008'; //[退换货]修改检测结果失败
    const CODE_33009 = '33009'; //[退换货]修改失败
    const CODE_34001 = '34001'; //[退换货]程序异常
    const CODE_34002 = '34002'; //[退换货]退款完成修改失败
    const CODE_34003 = '34003'; //[退换货]创建收货单失败
    const CODE_34004 = '34004'; //[退换货]拒绝退款失败
    const CODE_34005 = '34005'; //[退款]取消订单失败
    const CODE_34006 = '34006';//申请退货或换货单失败
    const CODE_34007 = '34007';//获取数据失败
    const CODE_34008 = '34008';//不允许进入退换货
    const CODE_34009 = '34009';//不支持退款审核拒绝
    const CODE_35009 = '35009';//[退换货]确认收货失败


    //-+----------------------------------------------------------------------
    // | 小程序订单
    //-+----------------------------------------------------------------------
    const CODE_35001= '35001'; //[小程序订单创建临时订单失败]
    const CODE_35002= '35002'; //[小程序订单本地订单查询失败]
    const CODE_35003= '35003'; //[小程序订单芝麻查询失败]
    const CODE_35004= '35004'; //[小程序订单同步返回状态错误]
    const CODE_35005= '35005'; //[小程序订单取消失败]
    const CODE_35006= '35006'; //[小程序订单扣款失败]
    const CODE_35007= '35007'; //[小程序扣款处理中请等待]
    const CODE_35008= '35008'; //[小程序添加风控信息处理失败]
    const CODE_35010= '35010'; //[小程序临时订单查询失败]
    const CODE_35011= '35011'; //[小程序appid匹配失败]
    const CODE_35012= '35012'; //[小程序确认订单失败]
    const CODE_35013= '35013'; //[小程序收货地址信息获取失败]
    const CODE_35014= '35014'; //[小程序商品获取失败]

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
    // | 发货错误信息
    //-+----------------------------------------------------------------------
    const CODE_42001 = '42001'; //[IMEI] excel数据导入失败






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
    const CODE_81001 = '81001'; //获取用户协议错误
    const CODE_81002 = '81002'; //预授权状态查询失败
    const CODE_81003 = '81003'; //预授权解冻失败
    const CODE_81004 = '81004'; //预授权转支付失败
	
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
	 * [还机][接口结果错误]接口结果错误!
	 */
    const CODE_93200 = '93200';
	/**
	 * [还机][程序异常]程序异常!
	 */
    const CODE_94000 = '94000';

	public static $errCodes = [
        self::CODE_0     => 'success',
        self::CODE_10100 => '空请求',
        self::CODE_10102 => '[app_id]错误',
        self::CODE_10103 => '[method]错误',
        self::CODE_10104 => '[params]错误',

        self::CODE_20001 =>  '参数必须',
        self::CODE_20002 =>  '访问失败',
        self::CODE_20003 =>  '验证失败',
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

        //订单操作
        self::CODE_30011 => '确认订单失败',
        self::CODE_30012 => '订单确认收货失败',
        self::CODE_30013 => '订单确认修改收货地址失败',
        self::CODE_30014 => '订单发货系统失败',



        //取消订单返回信息
        self::CODE_31001 => '[取消订单]订单号不能为空',
        self::CODE_31002 => '[取消订单]修改订单状态失败',
        self::CODE_31003 => '[取消订单]修改商品库存失败',
        self::CODE_31004 => '[取消订单]还券失败',
        self::CODE_31005 => '[取消订单]关闭分期失败',
        self::CODE_31006 => '[取消订单]失败',
        self::CODE_31007 => '[取消订单]状态异常',
        self::CODE_31008 => '[取消订单]解除订单代扣协议失败',

        //获取订单
        self::CODE_32001 => '[获取订单详情]订单号不存在',
        self::CODE_32002 => '[获取订单详情]数据异常',
        self::CODE_30034 => '[获取风控信息]数据异常',
        self::CODE_30036 => '[获取出险详情信息]数据异常',

        //清算
        self::CODE_31202    => '[订单清算]操作失败',
        self::CODE_31203    => '[订单清算取消失败]', //
        self::CODE_31204    => '[订单清算操作成功]', //
        self::CODE_31205    => '[订单清算不存在]', //

        //退换货信息
        self::CODE_33001 => '[退换货]退换货审核失败',
        self::CODE_33002 => '[退换货]退款审核失败',
        self::CODE_33003 => '[退换货]物流单号上传失败',
        self::CODE_33004 => '[退换货]取消退货申请失败',
        self::CODE_33005 => '[退换货]退换货结果查看失败',
        self::CODE_33006 => '[退换货]取消发货失败',
        self::CODE_33007 => '[退换货]取消退款失败',
        self::CODE_33008 => '[退换货]修改检测结果失败',
        self::CODE_33009 => '[退换货]修改失败',
        self::CODE_34001 => '[退换货]程序异常',
        self::CODE_34002 => '[退换货]退款完成修改失败',
        self::CODE_34003 =>'[退换货]创建收货单失败',
        self::CODE_34004 =>'[退换货]拒绝退款失败',
        self::CODE_34005 =>'[退换货]取消订单失败',
        self::CODE_34006 =>'[退换货]申请退货或换货单失败',
        self::CODE_34007 =>'[退换货]获取数据失败',
        self::CODE_34008 =>'[退换货]不允许进入退换货申请',
        self::CODE_34008 =>'[退款]不支持退款审核拒绝',
        self::CODE_35009 =>'[退换货]确认收货失败',


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

        self::CODE_50001 => '订单不存在',
        self::CODE_50002 => '订单商品不存在',
        self::CODE_50003 => '商品不存在',
        self::CODE_50010 => '优惠券不可用',
        self::CODE_50004 => '支付错误',
        self::CODE_50005 => '支付状态错误',

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
        self::CODE_81001 => '获取用户协议错误',
        self::CODE_81002 => '预授权状态查询失败',
        self::CODE_81003 => '预授权解冻失败',
        self::CODE_81004 => '预授权转支付失败',
		
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
		self::CODE_92300 => '[还机][接口调用结果错误]接口调用结果错误!',
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
