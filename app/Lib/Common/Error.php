<?php
namespace App\Lib\Common;

/**
 * Error
 *
 * @author Administrator
 */
class Error {
	
	/**
	 * 错误码
	 * @var string
	 */
	public static $errno;
	
	/**
	 * 错误提示
	 * @var string
	 */
	public static $error;
	
	/**
	 *
	 * @var Error 
	 */
	private static $instanc = null;
	
	/**
	 * 
	 */
	private function __construct() {}
	
	/**
	 * 
	 * @return Error
	 */
	public static function getInstanc( ){
		if( is_null(self::$instanc) ){
			self::$instanc = new self();
		}
		return self::$instanc;
	}
	
	public static function getErrno( ){
		return self::$errno;
	}
	
	public static function setErrno( string $errno,string $error=null ){
		self::$errno = $errno;
		self::$error = $error;
		return self::getInstanc();
	}
	
	public static function getError( ){
		return self::$error;
	}
	
	public static function setError( string $error,string $errno=null ){
		self::$error = $error;
		self::$errno = $errno;
		return self::getInstanc();
	}

	/**
	 * 使用异常信息填充
	 * @param \Exception $exc
	 */
	public static function exception( \Exception $exc ){
		self::$errno = $exc->getCode();
		self::$error = $exc->getMessage();
	}
	
}
