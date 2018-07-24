<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * ShortMessage
 */
interface ShortMessage {
	
	
	/**
	 * 设置业务类型
	 * @param int $business_type 业务类型
	 * @return \App\Order\Modules\Repository\ShortMessage\ShortMessage 
	 */
	public function setBusinessType( int $business_type );
	
	/**
	 * 设置业务编号
	 * @param string $business_no 业务编号
	 * @return  \App\Order\Modules\Repository\ShortMessage\ShortMessage
	 */
	public function setBusinessNo( string $business_no );

	/**
	 * 设置业务编号
	 * @param string $business_no 业务编号
	 * @return  \App\Order\Modules\Repository\ShortMessage\ShortMessage
	 */
	public function setData( array $data );
	
	/**
	 * 发送短息通知
	 * @return false
	 */
	public function notify();

	/**
	 * 支付宝内部消息通知
	 * @return false
	 */
	public function alipay_notify();
}
