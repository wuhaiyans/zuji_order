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
     * @var int 0
     */
    const STATUS_APPLYING = 0;
    /**
	 * 处理中【待收货】
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
	 * 逾期违约|结束
     * @var int 4
     */
    const STATUS_AGED_FAIL = 4;
    /**
	 * 交易完成
     * @var int 5
     */
    const STATUS_DEAL_DONE = 5;


    /**
     * 订单还机状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
            self::STATUS_APPLYING => '申请中',
            self::STATUS_DEAL_WAIT_DELIVERY => '处理中|待收货',
            self::STATUS_DEAL_WAIT_CHECK => '处理中|待检测',
            self::STATUS_DEAL_WAIT_PAY => '处理中|待支付',
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
}

