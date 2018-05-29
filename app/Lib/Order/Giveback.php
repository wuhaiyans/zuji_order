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
	 *		'goods_no' => '',//商品编号【必须】<br/>
	 * ]<br/>
	 * @return array $result 返回数据
	 * $result = [<br/>
	 *		'code' => '',//code码【0：成功;其它：失败】<br/>
	 *		'msg' => '',//详细信息<br/>
	 * ]<br/>
	 */
	public static function confirmDelivery( $params ) {
		return self::request(\env('APPID'), \env('API_INNER_URL'),'api.giveback.confirm.delivery', '1.0', $params);
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
	 * @return array $result 返回数据
	 * $result = [<br/>
	 *		'code' => '',//code码【0：成功;其它：失败】<br/>
	 *		'msg' => '',//详细信息<br/>
	 * ]<br/>
	 */
	public static function confirmEvaluation( $params ) {
		return self::request(\env('APPID'), \env('API_INNER_URL'),'api.giveback.confirm.evaluation', '1.0', $params);
	}

}