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
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getWithholdByUserId( int $user_id, int $channel ){
		sql_profiler();
		$info = \App\Order\Models\OrderPayWithhold::where([
			'user_id'	=> $user_id,
			'channel'	=> $channel,
		])->join()->first();
		var_dump( $info );exit;
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据业务系统支付编号 获取支付单
	 * @param string	$payment_no		支付编号
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByPaymentNo( string $payment_no ){
		$info = \App\Order\Models\OrderPayModel::where([
			'payment_no'	=> $payment_no,
		])->first();
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据业务系统 代扣协议编号 获取支付单
	 * @param string	$withhold_no		代扣协议编号
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByWithholdNo( string $withhold_no ){
		$info = \App\Order\Models\OrderPayModel::where([
			'withhold_no'	=> $withhold_no,
		])->first();
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据业务系统 资金授权编号 获取支付单
	 * @param string	$fundauth_no		资金授权编号
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByFundauthNo( string $fundauth_no ){
		$info = \App\Order\Models\OrderPayModel::where([
			'fundauth_no'	=> $fundauth_no,
		])->first();
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据 支付编号，获取支付系统支付信息
	 * @param string	$payment_no		支付编号
	 * @return array	
	 * [
	 *		'payment_no'		=> '',	//【必选】string 业务支付编码
	 *		'out_payment_no'	=> '',	//【必选】string  支付系统支付编码
	 *		'create_time'		=> '',	//【必选】int  创建时间戳
	 * ]
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPaymentInfoByPaymentNo( string $payment_no ){
		$info = \App\Order\Models\OrderPayPaymentModel::where([
			'payment_no'	=> $payment_no,
		])->first();
		if( $info ){
			return $info->toArray();
		}
		throw new \App\Lib\NotFoundException('支付系统支付信息不存在');
	}

    /**
     * 根据预授权编号，获取支付系统预授权信息
     * Author: heaven
     * @param string $authNo
     * @return array
     * @throws \App\Lib\NotFoundException
     */
    public static function getAuthInfoByAuthNo( string $authNo ){
        $info = \App\Order\Models\OrderPayFundauthModel::where([
            'fundauth_no'	=> $authNo,
        ])->first();
        if( $info ){
            return $info->toArray();
        }
        throw new \App\Lib\NotFoundException('支付系统预授权不存在');
    }
}
