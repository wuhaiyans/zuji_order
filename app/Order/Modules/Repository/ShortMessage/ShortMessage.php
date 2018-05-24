<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * ShortMessage
 */
interface ShortMessage {
	
	
	/**
	 * 设置业务类型
	 * @return string 
	 */
	public function setBusinessType( int $business_type );
	
	/**
	 * 设置业务编号
	 * @return string 
	 */
	public function setBusinessNo( int $business_no );
	
	/**
	 * 发送短息通知
	 * @return false
	 */
	public function notify();
	
}
