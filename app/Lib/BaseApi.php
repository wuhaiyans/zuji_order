<?php
namespace App\Lib;
/**
 * 接口基类
 * 定义了 系统之间交互接口基本方式
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
abstract class BaseApi {
	
	/**
	 * 获取API url地址
	 * @return string
	 */
	abstract protected static function getUrl():string;

		/**
     * 订单接口请求
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param	string	$method		接口名称
     * @param	array	$params		业务请求参数（具体业务查阅具体接口协议）
     * @return	array				业务返回参数（具体业务查阅具体接口协议）
	 * @throws \Exception			请求失败时抛出异常
     */
    public static function request(  string $method, array $params ){
		if( empty(self::$url) ){
			Common\LogApi::error('未指定接口地址');
			throw new \Exception('接口地址错误');
		}
		//-+--------------------------------------------------------------------
		// | 创建请求
		//-+--------------------------------------------------------------------
		$request = new \App\Lib\ApiRequest();
		$request->setUrl( self::$url );	// 接口地址
		$request->setAppid( 1 );		// 系统Appid
		$request->setMethod( $method );	// 接口名称
		$request->setVersion( 1.0 );
		$request->setParams( $params );	// 业务参数
		//-+--------------------------------------------------------------------
		// | 发送请求
		//-+--------------------------------------------------------------------
		$response = $request->sendPost();
		//-+--------------------------------------------------------------------
		// | 返回值处理
		//-+--------------------------------------------------------------------
		if( $response->isSuccessed() ){ // 判断执行是否成功，成功时返回业务返回值
			return $response->getData();
		}
		//-+--------------------------------------------------------------------
		// | 失败处理
		//-+--------------------------------------------------------------------
		$status = $response->getStatus();
		throw new \Exception($status->getMsg(),$status->getCode());
	}
}
