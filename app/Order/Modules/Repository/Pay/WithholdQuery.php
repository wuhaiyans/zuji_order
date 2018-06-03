<?php
namespace App\Order\Modules\Repository\Pay;

/**
 * 用户代扣协议查询
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class WithholdQuery {
	
	
	//-+------------------------------------------------------------------------
	// | 静态方法方法
	//-+------------------------------------------------------------------------
	/**
	 * 根据用户ID+支付渠道，查询代扣协议
	 * @param int		$user_id		用户ID
	 * @param int		$channel		支付渠道
	 * @return \App\Order\Modules\Repository\Pay\Withhold
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByUserChannel( int $user_id, int $channel ){
		//sql_profiler();
		$info = \App\Order\Models\OrderPayWithholdModel::where([
			'user_id'	=> $user_id,
			'withhold_channel'	=> $channel,
			'withhold_status'	=> WithholdStatus::SIGNED,
		])->first();
		if( $info ){
			return new Withhold( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('代扣协议不存在');
	}
	
}
