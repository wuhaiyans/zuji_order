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
	 * 错误数据
	 * @var mixed
	 */
	public static $data;
	
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
	
	public static function setErrno( string $errno, string $error=null ){
		self::$errno = $errno;
		self::$error = $error;
		return self::getInstanc();
	}
	
	public static function getError( ){
		return self::$error;
	}
	
	public static function setError( string $error, $data=null , string $errno=null ){
		self::$error = $error;
		self::$data	 = $data;
		self::$errno = $errno;
		return self::getInstanc();
	}
	
	public static function getData( ){
		return self::$data;
	}
	
	public static function setData( $data ){
		self::$data = $data;
		return self::getInstanc();
	}

	/**
	 * 使用异常信息填充
	 * @param \Exception $exc
	 */
	public static function exception( \Exception $exc ){
		self::$errno = $exc->getCode();
		self::$error = $exc->getMessage();
		self::$data = $exc;
	}
	
}
