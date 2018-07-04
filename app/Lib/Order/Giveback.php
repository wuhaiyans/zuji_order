<?php
namespace App\Lib\Order;

/**
 * Class Giveback
 * 订单系统：还机接口调用类
 * @author Yao Dong Xu <yaodongxu@huishoubao.com.cn>
 * @date 2018-05-29 16:02:12
 */
class Giveback extends \App\Lib\BaseApi
{
	/**
	 * 确认收货
	 * @param array $params
	 * $params = [<br/>
	 *		['goods_no' => ''],//商品编号【必须】<br/>
	 * ]<br/>
	 * @return mixed boolen：true成功；obj:\exception
	 */
	public static function confirmDelivery( $params ) {
		foreach ($params as $value) {
			self::request(\env('APPID'), \env('ORDER_API'),'api.giveback.confirm.delivery', '1.0', $value);
		}
		return true;
	}
	
	/**
	 * 确认检测结果
	 * @param array $params
	 * $params = [<br/>
	 *		'goods_no' => '',//商品编号<br/>
	 *		'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
	 *		'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
	 *		'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
	 * ]<br/>
	 * @return mixed boolen：true成功；obj:\exception
	 */
	public static function confirmEvaluation( $params ) {
		if( self::request(\env('APPID'), \env('ORDER_API'),'api.giveback.confirm.evaluation', '1.0', $params) ){
			return true;
		}
	}
	/**
	 * 确认检测结果【接收二维参数】
	 * @param array $params
	 * $params = [<br/>
	 *		'goods_no' => '',//商品编号<br/>
	 *		'evaluation_status' => '',//检测状态【必须】【1：合格；2：不合格】<br/>
	 *		'evaluation_time' => '',//检测时间（时间戳）【必须】<br/>
	 *		'evaluation_remark' => '',//检测备注【可选】【检测不合格时必有】<br/>
	 *		'compensate_amount' => '',//赔偿金额【可选】【检测不合格时必有】<br/>
	 * ]<br/>
	 * @return mixed boolen：true成功；obj:\exception
	 */
	public static function confirmEvaluationArr( $params ) {
		foreach ( $params as $param ){
			self::confirmEvaluation($param);
		}
		return true;
	}

}