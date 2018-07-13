<?php
/**
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/4/27
 * Time: 11:26
 */
namespace App\Order\Modules\Inc;

class OrderGivebackStatus{

	//-+------------------------------------------------------------------------
	// | 还机单状态定义
	//-+------------------------------------------------------------------------
    /**
	 * 申请中【只给前端展示使用，数据库不会存储当前字段】
     * @var int -1
     */
    const STATUS_APPLYING = -1;
    /**
    /**
	 * 全部状态
     */
    const STATUS_ALL = 0;
    /**	 * 处理中【待收货】
     * @var int 1
     */
    const STATUS_DEAL_WAIT_DELIVERY = 1;
    /**
	 * 处理中【待检测】
     * @var int 2
     */
    const STATUS_DEAL_WAIT_CHECK = 2;
    /**
	 * 处理中【待支付】
     * @var int 3
     */
    const STATUS_DEAL_WAIT_PAY = 3;
    /**
	 * 处理中【支付确认中】
     * @var int 4
     */
    const STATUS_DEAL_IN_PAY = 4;
    /**
	 * 处理中【押金退还中】【待清算】
     * @var int 5
     */
    const STATUS_DEAL_WAIT_RETURN_DEPOSTI = 5;
    /**
	 * 逾期违约|结束
     * @var int 6
     */
    const STATUS_AGED_FAIL = 6;
    /**
	 * 交易完成
     * @var int 7
     */
    const STATUS_DEAL_DONE = 7;


    /**
     * 订单还机状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
			self::STATUS_ALL => '全部',
            self::STATUS_DEAL_WAIT_DELIVERY => '处理中|待收货',
            self::STATUS_DEAL_WAIT_CHECK => '处理中|待检测',
            self::STATUS_DEAL_WAIT_PAY => '处理中|待支付',
            self::STATUS_DEAL_IN_PAY => '处理中|支付确认中',
            self::STATUS_DEAL_WAIT_RETURN_DEPOSTI => '处理中|押金退还中',
            self::STATUS_AGED_FAIL => '逾期违约|关闭',
            self::STATUS_DEAL_DONE => '交易完成|关闭',
        ];
    }

    /**
     * 订单还机状态值 转换成 状态名称
     * @param int $status   订单还机状态值
     * @return string 订单还机状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
	
	
	//-+------------------------------------------------------------------------
	// | 前端还机单状态定义
	//-+------------------------------------------------------------------------
	/**
	 * 前端申请还机
	 */
	const VIEW_STATUS_APPLYING = 1;
	/**
	 * 前端还机检测
	 */
	const VIEW_STATUS_CHECK = 2;
	/**
	 * 前端退还押金
	 */
	const VIEW_STATUS_RETURN_DEPOSTI= 3;
	/**
	 * 后台状态对应前端状态值
	 * @param $status int 后台状态值
	 * @return $status int 前端状态值
	 */
	public static function adminMapView( $status ) {
		$map = [
            self::STATUS_APPLYING => self::VIEW_STATUS_APPLYING,
            self::STATUS_DEAL_WAIT_DELIVERY => self::VIEW_STATUS_APPLYING,
            self::STATUS_DEAL_WAIT_CHECK => self::VIEW_STATUS_CHECK,
            self::STATUS_DEAL_WAIT_PAY => self::VIEW_STATUS_CHECK,
            self::STATUS_DEAL_IN_PAY => self::VIEW_STATUS_CHECK,
            self::STATUS_DEAL_WAIT_RETURN_DEPOSTI => self::VIEW_STATUS_RETURN_DEPOSTI,
            self::STATUS_AGED_FAIL => self::VIEW_STATUS_CHECK,
            self::STATUS_DEAL_DONE => self::VIEW_STATUS_RETURN_DEPOSTI,
		];
		if( isset($map[$status]) ){
			return $map[$status];
		}
		return fasle;
	}
    /**
     * 订单还前端状态列表
     * @return array
     */
    public static function getViewStatusList(){
        return [
			self::VIEW_STATUS_APPLYING => '申请还机',
			self::VIEW_STATUS_CHECK => '还机检测',
			self::VIEW_STATUS_RETURN_DEPOSTI => '退还押金',
        ];
    }

    /**
     * 订单还机前端状态值 转换成 状态名称
     * @param int $status   订单还机状态值
     * @return string 订单还机状态名称
     */
    public static function getViewStatusName($status){
        $list = self::getViewStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


	//-+------------------------------------------------------------------------
	// | 还机单支付状态定义
	//-+------------------------------------------------------------------------
    /**
	 * 待检测验证
     * @var int 0
     */
    const PAYMENT_STATUS_INIT_PAY = 0;
    /**
	 * 无需支付
     * @var int 1
     */
    const PAYMENT_STATUS_NODEED_PAY = 1;
    /**
	 * 支付中
     * @var int 2
     */
    const PAYMENT_STATUS_IN_PAY = 2;
    /**
	 * 已支付
     * @var int 3
     */
    const PAYMENT_STATUS_ALREADY_PAY = 3;
    /**
	 * 未支付
     * @var int 4
     */
    const PAYMENT_STATUS_NOT_PAY = 4;
    /**
     * 订单还机支付状态列表
     * @return array
     */
    public static function getPaymentStatusList(){
        return [
            self::PAYMENT_STATUS_INIT_PAY => '待检测验证是否支付',
            self::PAYMENT_STATUS_NODEED_PAY => '无需支付',
            self::PAYMENT_STATUS_IN_PAY => '支付中',
            self::PAYMENT_STATUS_ALREADY_PAY => '已支付',
            self::PAYMENT_STATUS_NOT_PAY => '未支付',
        ];
    }

    /**
     * 订单还机支付状态值 转换成 状态名称
     * @param int $status   订单还机支付状态值
     * @return string 订单还机支付状态名称
     */
    public static function getPaymentStatusName($status){
        $list = self::getPaymentStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

	//-+------------------------------------------------------------------------
	// | 还机单检测状态定义
	//-+------------------------------------------------------------------------
    /**
	 * 未检测
     * @var int 0
     */
    const EVALUATION_STATUS_INIT = 0;
    /**
	 * 检测合格
     * @var int 1
     */
    const EVALUATION_STATUS_QUALIFIED = 1;
    /**
	 * 检测不合格
     * @var int 2
     */
    const EVALUATION_STATUS_UNQUALIFIED = 2;
    /**
     * 订单还机检测状态列表
     * @return array
     */
    public static function getEvaluationStatusList(){
        return [
            self::EVALUATION_STATUS_INIT => '未检测',
            self::EVALUATION_STATUS_QUALIFIED => '检测合格',
            self::EVALUATION_STATUS_UNQUALIFIED => '检测不合格',
        ];
    }

    /**
     * 订单还机检测状态值 转换成 状态名称
     * @param int $status   订单还机检测状态值
     * @return string 订单还机检测状态名称
     */
    public static function getEvaluationStatusName($status){
        $list = self::getEvaluationStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
	//-+------------------------------------------------------------------------
	// | 还机单押金状态定义
	//-+------------------------------------------------------------------------
    /**
	 * 初始化：无意义
     * @var int 0
     */
    const YAJIN_STATUS_INIT = 0;
    /**
	 * 无需退还押金
     * @var int 1
     */
    const YAJIN_STATUS_NO_NEED_RETURN = 1;
    /**
	 * 押金退还中
     * @var int 2
     */
    const YAJIN_STATUS_IN_RETURN = 2;
    /**
	 * 押金退还完成
     * @var int 3
     */
    const YAJIN_STATUS_RETURN_COMOLETION = 3;
    /**
	 * 押金退还失败
     * @var int 4
     */
    const YAJIN_STATUS_RETURN_FAIL = 4;
    /**
     * 订单还机押金退还状态列表
     * @return array
     */
    public static function getYajinStatusList(){
        return [
            self::YAJIN_STATUS_NO_NEED_RETURN => '无需退还押金',
            self::YAJIN_STATUS_IN_RETURN => '退还押金中',
            self::YAJIN_STATUS_RETURN_COMOLETION => '押金退还完成',
            self::YAJIN_STATUS_RETURN_FAIL => '押金退还失败',
        ];
    }

    /**
     * 订单还机押金退还状态值 转换成 状态名称
     * @param int $status   订单还机押金退还状态值
     * @return string 订单还机押金退还状态名称
     */
    public static function getYajinStatusName($status){
        $list = self::getYajinStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
	//-+------------------------------------------------------------------------
	// | 还机单代扣状态定义
	//-+------------------------------------------------------------------------
    /**
	 * 初始化：无意义
     * @var int 0
     */
    const WITHHOLD_STATUS_INIT = 0;
    /**
	 * 无需代扣
     * @var int 1
     */
    const WITHHOLD_STATUS_NO_NEED_WITHHOLD = 1;
    /**
	 * 代扣已执行【不考虑后果】
     * @var int 2
     */
    const WITHHOLD_STATUS_ALREADY_WITHHOLD = 2;
    /**
     * 订单还机代扣状态列表
     * @return array
     */
    public static function getWithholdStatusList(){
        return [
            self::YAJIN_STATUS_NO_NEED_RETURN => '无需代扣',
            self::WITHHOLD_STATUS_ALREADY_WITHHOLD => '代扣已执行',
        ];
    }

    /**
     * 订单还机代扣状态值 转换成 状态名称
     * @param int $status   订单还机代扣状态值
     * @return string 订单还机代扣状态名称
     */
    public static function getWithholdStatusName($status){
        $list = self::getWithholdStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
}

