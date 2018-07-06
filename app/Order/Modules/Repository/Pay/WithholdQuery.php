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
	 * 当前有效的代扣协议
	 * @param int		$user_id		用户ID
	 * @param int		$channel		支付渠道
	 * @return \App\Order\Modules\Repository\Pay\Withhold
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByUserChannel( int $user_id, int $channel, $lock=null ){
		//sql_profiler();
		$builder = \App\Order\Models\OrderPayWithholdModel::where([
			'user_id'	=> $user_id,
			'withhold_channel'	=> $channel,
			'withhold_status'	=> WithholdStatus::SIGNED,
		])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
		$info = $builder->first();
		if( $info ){
			return new Withhold( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('代扣协议不存在');
	}

	/**
	 * 根据代扣协议编号 ，查询代扣协议
	 * 允许查询已经解约的代扣协议
	 * @param string		$withhold_no		代扣协议编号
	 * @return \App\Order\Modules\Repository\Pay\Withhold
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByWithholdNo( string $withhold_no, $lock=null ){

		$builder = \App\Order\Models\OrderPayWithholdModel::where([
			'withhold_no'	=> $withhold_no,
		])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
		$info = $builder->first();
		if( $info ){
			return new Withhold( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('代扣协议不存在');
	}

	/**
	 * 根据 业务 ，查询代扣协议
	 * @param int			$bu_type
	 * @param string		$bu_no
	 * @return \App\Order\Modules\Repository\Pay\Withhold
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getByBusinessNo( int $bu_type, string $bu_no ){
	    $where[]=['business_type','=',$bu_type];
        $where[]=['business_no','=',$bu_no];
        $where[]=['bind_time','>',0];
        $where[]=['unbind_time','=',0];
        $info = \App\Order\Models\OrderPayWithholdBusinessModel::where($where)->first();
		/*$info = \App\Order\Models\OrderPayWithholdBusinessModel::where([
			'business_type'	=> $bu_type,
			'business_no'	=> $bu_no,
			'bind_time'		=> ['>',0],
			'unbind_time'	=> 0,
		])->first();*/
		if( $info ){
			return self::getByWithholdNo( $info->withhold_no );
		}
		throw new \App\Lib\NotFoundException('代扣协议不存在');
	}

}
