<?php
/**
 * Curl 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */
namespace App\Lib;

/**
 * Curl类
 *
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class Curl {
    
	/**
	 * 错误标记 0：没有错误
	 * @var int
	 */
	private static $errno = 0;
	
	/**
	 * 错误提示
	 * @var string
	 */
	private static $error = '';
	
	
	/**
	 * 超时时间
	 * @var int
	 */
	public static $timeout = 10;
	
	/**
	 *
	 * @var type 
	 */
	public static $curl_info = null;
	
	
	/**
	 * 判断执行是否
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return bool
	 */
	public static function hasError():bool{
		return self::$errno>0;
	}
	/**
	 * 
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return string
	 */
	public static function getError():string{
		return self::$error;
	}
	/**
	 * 
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @return string
	 */
	public static function getErrno():string{
		return self::$errno;
	}
	/**
	 * 
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param array $data
	 * @return string
	 */
	public static function build_query(array $data):string{
		return http_build_query( $data);
	}
	
	/**
     * GET 方式执行 HTTP请求
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param string	$url   完整url地址
     * @return string
     */    
    public static function get($url){
        $output = self::_send($url);
        return $output;
    }
    
    /**
     * POST 方式执行 HTTP请求
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param string	$url   完整url地址
     * @param mixed		$params	请求参数；string（k=v&k=v格式的字符串）；array('k'=>'v')形式的关联数组
     * @return string
     */    
    public static function post(string $url, $params=null,array $header=[]){

		if(is_array($params) ){
			$params = http_build_query( $params );
		}


        $output = self::_send($url, $params, $header);
        return $output;
    }


    /**
     * @param $url
     * @param $params
     *
     * 提交数组
     */
    public static function postArray($url, $params, $header=array(), $json=false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        // https请求不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // header 头参数
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //至关重要，CURLINFO_HEADER_OUT选项可以拿到请求头信息
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
        // curl_setopt($ch, CURLOPT_HEADER, true);
        // 发送请求

        if ($json) {
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length:' . strlen($params)
            ]);
        }
        $output = self::_curl_exec($ch,$url,$params);
        curl_close($ch);
        return $output;
    }
    
	/**
	 * 
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 * @param string $url		请求地址
	 * @param array $params		请求参数 array('k'=>'v')形式的关联数组
	 * @param array $header		请求头 array('k'=>'v')形式的关联数组
	 * @return string
	 */
    private static function _send(string $url,string $params=null,array $header=[]){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if( $params ){
			// post数据
			curl_setopt($ch, CURLOPT_POST, true);
			// post的变量
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
        // https请求不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		// header 头参数
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		//至关重要，CURLINFO_HEADER_OUT选项可以拿到请求头信息
		curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
		// 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
		// curl_setopt($ch, CURLOPT_HEADER, true);
		// 发送请求
        $output = self::_curl_exec($ch);
		self::$curl_info = curl_getinfo($ch);
        curl_close($ch);
        return $output;
    }
	
    /**
     * 执行 curl 请求，如果失败，重试3次
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param object	$ch        curl 对象
     * @param string	$url       请求地址
     * @param mixed		$params     参数（string 或 数组）
     * @return mixed   string：成功，返回请求结果；false：失败
     */
    private static function _curl_exec( $ch){
        $i = 3;
        while( $i ){
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);// 设置超时
            $output = curl_exec($ch);
			self::$errno = curl_errno($ch);
            if ( self::$errno == 0 ) {// 成功，直接返回
				self::$error = '';
                return $output;
            }
			self::$error = curl_error($ch);
			self::$errno = curl_errno($ch);
            --$i;
        }
        return false;
    }
    
}
