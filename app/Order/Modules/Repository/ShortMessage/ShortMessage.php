<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * ShortMessage
 */
interface ShortMessage {
	
	
	/**
	 * 短息模板ID
	 * @return string 
	 */
	public function getCode();
	
	/**
	 * 发送短息通知
	 * @return false
	 */
	public function notify();
	
}
