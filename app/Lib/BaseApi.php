<?php
namespace App\Lib;
/**
 * Description of BaseApi
 *
 * @author Administrator
 */
class BaseApi {
	
	protected static $error = '';
	
	public static function getError(){
		return self::$error;
	}
}
