<?php
namespace App\Lib\Common;

use App\Lib\Curl;

/**
 * 
 * @author liuhongxing
 */
class LogApi {
		
	/**
	 * 业务数据日志
	 * @param string	$msg
	 * @param mixed		$data
	 */
	public static function info( string $msg, $data=[] )
	{
		self::log('Info', $msg, $data);
	}
	
	/**
	 * 业务通知日志
	 * @param string	$msg
	 * @param mixed		$data
	 */
	public static function notify( string $msg, $data=[] )
	{
		self::log('Notify', $msg, $data);
	}
	
	/**
	 * 程序调试日志
	 * @param string	$msg
	 * @param mixed		$data
	 */
	public static function debug( string $msg, $data=[] )
	{
		self::log('Debug', $msg, $data);
	}
	
	/**
	 * 错误日志
	 * @param string	$msg
	 * @param mixed		$data
	 */
	public static function error( string $msg, $data=[] )
	{
		self::log('Error', $msg, $data);
	}
	
	/**
	 * 日志
	 * @param string $level		日志级别
	 * @param string $msg		日志内容
	 * @param mixed		$data
	 */
	private static function log( string $level, string $msg, $data=[] )
	{
		if( is_array( $data ) || is_object( $data ) )
		{
			$data = json_encode($data);
		}
		elseif( is_string( $data ) || is_numeric($data) ){
			$data .= ''; 
		}
		else{
			$data = json_encode($data);
		}
		$traces = debug_backtrace();
		$str = sprintf("%s:(%d):%s\t[%s]:\t%s\t%s\n", 
				substr( $traces[1]['file'], strlen(app_path() ) ),
				$traces[1]['line'],
				$traces[2]['function'],
				$level,
				$msg,
				trim($data));
        file_put_contents('./jobtest.log', $str, FILE_APPEND);
		dispatch(new \App\Jobs\LogJob($str));
		
		$_config = [
			'service' => gethostname(),					// 服务器名称
			'source' => env('LOG_SOURCE'),				// 日志来源
			'message' => $msg,
			'host' => request()->server('HTTP_HOST'),	// 	Host名称
			'data' => [
				'level' => $level,						// 级别
				'session_id' => session_id(),			// 回话
				'user_id' => '',						// 用户ID
				'serial_no' => self::_autoincrement(),	// 序号
				'content' => $data,						// 内容
			],
		];
		dispatch(new \App\Jobs\LogJob( json_encode($_config) ));
		try {
			// 请求
			$res = Curl::post(env('LOG_API'), json_encode($_config));
			if( !$res ){
				return false;
			}
			$res = json_decode($res,true);
			if( !$res ){
				return false;
			}
			if( $res['code']!='0'){ // 非0为不正常，记录本地日志
				dispatch(new \App\Jobs\LogJob( $str ));
			}
			
		} catch (\Exception $exc) {
			dispatch(new \App\Jobs\LogJob( '日志错误 '.$exc->getMessage().' '.json_encode($_config) ));
		}

		
		return true;
	}
	
	// 自增
	private static $counter =0 ;
	private static function _autoincrement() {
		return ++self::$counter;
	}
}
