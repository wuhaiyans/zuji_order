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
	 * 发送短息通知
	 * @return false
	 */
	public function notify($data = []);
	
}
