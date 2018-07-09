<?php
namespace App\Lib\Common;

use App\Lib\Curl;
//将时区设置为中国
date_default_timezone_set("PRC");
//将时区设置为上海时区
ini_set('date.timezone','Asia/Shanghai');

/**
 * JobQueueApi 工作队列
 * @author liuhongxing
 */
class JobQueueApi {
	
	/**
	 * 实时任务
	 * @param string $key	任务唯一标识
	 * @param string $url	任务地址
	 * @param array	  $data	数据
	 * @param string $callback	任务执行完成后的回调通知地址
	 * @return bool
	 */
	public static function addRealTime( string $key, string $url, array $data, string $callback='' ):bool{
		return self::push($key, $url, $data, $callback, 'realTime');
	}
	
	/**
	 * 定时任务()
	 * @param string $key	任务唯一标识
	 * @param string $url	任务地址
	 * @param array	  $data	数据
	 * @param int $timestamp	开始时间戳
	 * @param string $callback	任务执行完成后的回调通知地址
	 * @return bool
	 */
	public static function addScheduleOnce( string $key, string $url, array $data,int $timestamp, string $callback='' ):bool{

		$start = '@at '.date('Y-m-d',$timestamp)."T".date('H:i:s',$timestamp).'+08:00';

		return self::push($key, $url, $data, $callback, 'scheduled',$start);
	}
	/**
	 * 循环任务()
	 * @param string $key	任务唯一标识
	 * @param string $url	任务地址
	 * @param array	  $data	数据
	 * @param string $interval	时间间隔 1h30m10s	按时间间隔循环执行	1小时30分10秒"
	 * @param string $callback	任务执行完成后的回调通知地址
	 * @return bool
	 */
	public static function addScheduleEvery( string $key, string $url, array $data,string $interval, string $callback='' ):bool{
		$start = '@every '.$interval;
		return self::push($key, $url, $data, $callback, 'scheduled',$start);
	}
	
	/**
	 * cron任务
	 * @param string $key	任务唯一标识
	 * @param string $url	任务地址
	 * @param array	  $data	数据
	 * @param string $cron
	 * @param string $callback	任务执行完成后的回调通知地址
	 * @return bool
	 */
	public static function addScheduleCron( string $key, string $url, array $data, string $cron, string $callback='' ):bool{
		return self::push($key, $url, $data, $callback, 'cron','',$cron);
	}
	
	
	/**
	 * 
	 * @param string $key	任务唯一标识
	 * @param string $url	任务地址
	 * @param array	  $data	数据
	 * @param string $type realTime：实时任务(任务添加后立即执行)；scheduled：定时任务；cron：cron格式计划任务
	 * @param string $start  
	 * "@at 2018-04-02T02:55:30Z	按指定时间执行	只执行一次，Z代表0时区"
	 * "@every 1h30m10s	按时间间隔循环执行	1小时30分10秒"
	 * @param string $cron "cron 表达式格式"
	 * @return bool
	 */
	private static function push( string $key, string $url, array $data, string $callback='', string $type='realTime', string $start='',string $cron='' ):bool{
		if( $callback == '' ){
			$callback = config('jobsystem.JOB_CALLBACK');
		}
		$_config = [
			'interface' => 'jobAddAsync',
			'auth' => config('jobsystem.JOB_AUTH'),
			'name' => $key,
			'desc' => '',
			'type' => $type,
			'url' => $url,
			'data' => $data,
			'callback' => $callback,
			'start' => $start,
			'cron' => $cron,
			'retries' => 3, // 错误重试次数
		];
		LogApi::info('任务-'.$key,$_config);
		// 请求
		$res = Curl::post(config('jobsystem.JOB_API'), json_encode($_config), ['Content-Type: application/json']);
		if( !$res ){
			LogApi::type('third-api')::error('任务系统请求失败', [
				'url' => config('jobsystem.JOB_API'),
				'params' => $_config,
			]);
			return false;
		}
		$res = json_decode($res,true);
		if( !$res ){
			LogApi::type('third-api')::error('任务系统请求结果错误', [
				'url' => config('jobsystem.JOB_API'),
				'params' => $_config,
				'result' => $res,
			]);
			return false;
		}
		if( $res['status']=='ok'){
			return true;
		}
		LogApi::type('third-api')::error('任务系统请求状态失败', [
			'url' => config('jobsystem.JOB_API'),
			'params' => $_config,
			'result' => $res,
		]);
		return false;
	}
	/**
	 * 取消任务
	 * @param string $key
	 * @return bool
	 */
	public static function cancel( string $key):bool{
		$_config = [
			'interface' => 'jobDelAsync',
			'auth' => config('jobsystem.JOB_AUTH'),
			'name' => $key,
		];
		// 请求
		$res = Curl::post(config('jobsystem.JOB_API'), json_encode($_config), ['Content-Type: application/json']);
		if( !$res ){
			return false;
		}
		$res = json_decode($res,true);
		if( !$res ){
			return false;
		}
		if( $res['status']=='ok'){
			return true;
		}
		return true;
	}
	
}
