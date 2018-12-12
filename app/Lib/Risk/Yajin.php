<?php
namespace App\Lib\Risk;

/**
 * 风控押金接口
 */
class Yajin extends \App\Lib\BaseApi {
	
	/**
	 * 根据风控结果，计算押金
	 * @param array		
	 * [
	 *		'appid'			=> '',	// 【必选】渠道入口ID（微回收入口时必须）
	 *		'zujin'			=> '',	// 【必选】商品总租金，单位：分
	 *		'yajin'			=> '',	// 【必选】商品总押金，单位：分
	 *		'market_price'	=> '',	// 【必选】商品市场价格，单位：分
	 *		'user_id'		=> '',	// 【必选】用户ID
	 *		'is_order'		=> '',	// 【必选】是否是订单 0 否 1是 默认1
	 * ]
	 * @return array
	 * [
	 *		'yajin'			=> '', // 实际押金，单位：分
	 *		'jianmian'		=> '', // 减免押金，单位：分
	 *		'jianmian_detail' => [	// 减免明细列表
	 *			[
	 *				'type'		=> '',	// 减免分类，取值范围：["realname","mco","eb"] realname:实名认证；mco：移动运营商认证；eb：电商认证；
	 *				'jianmian'	=> '',	// 减免押金，单位：分
	 *			]
	 *		],
	 * ]
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public static function calculate( array $params ):array{
		return self::request('1', \config('risksystem.FENGKONG_API'), 'fengkong.yajin.calculate', '1.0', $params);
	}

	/**
     *  下单 之后调用风控押金（免押金额减少）
     * @param [
     *      'user_id'		=> '',	// 【必选】用户ID
     *      'order_no'      =>'',   // 【必选】订单编号
     *      'jianmian'      =>'',   // 【必选】押金减免值 单位：分
     *      'appid'      =>'',      // 【必选】渠道APPID
     * ]
     * @return array
     */
    public static function MianyajinReduce( array $params ):array{
        return self::request('1', \config('risksystem.FENGKONG_API'), 'fengkong.yajin.order', '1.0', $params);
    }

    /**
     *  订单完结 之后调用风控押金（免押金额减少）
     * @param [
     *      'user_id'		=> '',	// 【必选】用户ID
     *      'order_no'      =>'',   // 【必选】订单编号
     *      'type'      =>'',       // 【必选】订单完结类型 1订单正常结束，2订单取消，默认为1正常结束，此参数可不传
     * ]
     * @return array
     */
    public static function OrderComplete( array $params ):array{
        return self::request('1', \config('risksystem.FENGKONG_API'), 'fengkong.yajin.complete', '1.0', $params);
    }

}
