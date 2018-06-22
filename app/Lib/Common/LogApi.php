<?php
namespace App\Lib\Common;

use App\Lib\Curl;

/**
 * 
 * @author liuhongxing
 */
class LogApi {
		
	private static $instance;
	public static function getInstace(){
		if( self::$instance ){
			return self::$instance;
		} 
		return new self();
	}
	
	private static $source = 'default';
	public static function setSource( string $source ){
		self::$source = $source;
		return self::getInstace();
	}

	/**
	 * 类型
	 * @var string
	 */
	private static $type = '';
	public static function type( string $type )
	{
		self::$type = $type;
		return self::getInstace();
	}
	
	/**
	 * key
	 * @var string
	 */
	private static $key = '';
	public static function key( string $key )
	{
		self::$key = $key;
		return self::getInstace();
	}
	/**
	 * 程序调试日志
	 * @param string	$msg
	 * @param mixed		$data
	 * @return LogApi
	 */
	public static function debug( string $msg, $data=[] )
	{
		if(config('logsystem.LOG_REPORT') == 'debug' ){
			return self::log('Debug', $msg, $data);
		}
		return self::getInstace();
	}
	
	/**
	 * 业务数据日志
	 * @param string	$msg
	 * @param mixed		$data
	 * @return LogApi
	 */
	public static function info( string $msg, $data=[] )
	{
		return self::log('Info', $msg, $data);
	}
	
	/**
	 * 业务通知日志
	 * @param string	$msg
	 * @param mixed		$data
	 * @return LogApi
	 */
	public static function notify( string $msg, $data=[] )
	{
		return self::log('Notify', $msg, $data);
	}
	
	/**
	 * 警告日志
	 * @param string	$msg
	 * @param mixed		$data
	 * @return LogApi
	 */
	public static function warn( string $msg, $data=[] )
	{
		return self::log('Warning', $msg, $data);
	}
	
	/**
	 * 错误日志
	 * @param string	$msg
	 * @param mixed		$data
	 * @return LogApi
	 */
	public static function error( string $msg, $data=[] )
	{
		return self::log('Error', $msg, $data);
	}
	
	/**
	 * 日志
	 * @param string $level		日志级别
	 * @param string $msg		日志内容
	 * @param mixed		$data
	 */
	private static function log( string $level, string $msg, $data=[] )
	{
		// 异常
		if( $data instanceof \Exception ){
			$data = json_encode([
				'Code'		=> $data->getCode(),
				'Message'	=> $data->getMessage(),
				'File' => $data->getFile(),
				'Line' => $data->getLine(),
			]);
		}
		elseif( is_array( $data ) || is_object( $data ) )
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
		$str = sprintf("%s\t%s:(%d):%s\t[%s]:\t%s\t%s\n", 
				date('Y-m-d H:i:s'),
				substr( $traces[1]['file'], strlen(app_path() ) ),
				$traces[1]['line'],
				$traces[2]['function'],
				$level,
				$msg,
				trim($data));
		$job = new \App\Jobs\LogJob($str);
		//$job->delay(5);
		dispatch( $job );
		
		$_data = [
			'service' => gethostname(),					// 服务器名称
			'source' => self::$source,					// 日志来源
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
		// Redis 发布
		//$job = new \App\Jobs\Log2Job( $_data );
		//dispatch( $job );
		\Illuminate\Support\Facades\Redis::PUBLISH('zuji.log.publish', json_encode( $_data ) );
		
		// 日志系统接口
		try {
			// 请求
			$res = Curl::post(config('logsystem.LOG_API'), json_encode($_data));
			
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
			dispatch(new \App\Jobs\LogJob( '日志错误 '.$exc->getMessage().' '.json_encode($_data) ));
		}

		
		return self::getInstace();
	}
	
	// 自增
	private static $counter =0 ;
	private static function _autoincrement() {
		return ++self::$counter;
	}
}
