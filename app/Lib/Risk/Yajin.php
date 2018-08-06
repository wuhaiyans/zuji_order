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
}
