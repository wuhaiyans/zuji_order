<?php
namespace App\Lib\Common;

use App\Lib\Curl;

/**
 * 
 * @author liuhongxing
 */
class LogApi {
	
	private static $_url = 'http://job-api.hsbbj.com/api';
	
	private static $_auth = '7ZT%SC8HB4*Ad$bWyEaj2mBy%qd2G49A';
	
	
	/**
	 * 业务数据日志
	 * @param string	$msg
	 * @param array		$data
	 */
	public static function info( string $msg, array $data=[] )
	{
		self::log('Info', $msg, $data);
	}
	
	/**
	 * 业务通知日志
	 * @param string	$msg
	 * @param array		$data
	 */
	public static function notify( string $msg, array $data=[] )
	{
		self::log('Notify', $msg, $data);
	}
	
	/**
	 * 程序调试日志
	 * @param string	$msg
	 * @param array		$data
	 */
	public static function debug( string $msg, array $data=[] )
	{
		self::log('Debug', $msg, $data);
	}
	
	/**
	 * 错误日志
	 * @param string	$msg
	 * @param array		$data
	 */
	public static function error( string $msg, array $data=[] )
	{
		self::log('Error', $msg, $data);
	}
	
	/**
	 * 日志
	 * @param string $level		日志级别
	 * @param string $msg		日志内容
	 * @param array		$data
	 */
	private static function log( string $level, string $msg, array $data=[] )
	{
		$traces = debug_backtrace();
//		var_dump( $traces[2] );
		$str = sprintf("%s:(%d):%s\t[%s]:\t%s\n%s", 
				substr( $traces[1]['file'], strlen(app_path() ) ),
				$traces[1]['line'],
				$traces[2]['function'],
				$level,
				$msg,
				count($data)?var_export($data,true)."\n":'');
		echo $str;
//		$_config = [
//			'interface' => 'jobDisable',
//			'auth' => self::$_auth,
//			'name' => $key,
//		];
//		// 请求
//		$res = Curl::post(self::$_url, json_encode($_config));
//		if( !$res ){
//			return false;
//		}
//		$res = json_decode($res,true);
//		if( !$res ){
//			return false;
//		}
//		if( $res['status']=='ok'){
//			return true;
//		}
//		return true;
	}
}
