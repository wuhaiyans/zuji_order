<?php
/**
 * 
 *
 * @author Administrator
 */
class Curl {
    
    /**
     * GET 方式执行 HTTP请求
     * @param string	$url   完整url地址
     * @return string
     */    
    public static function get($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // https请求不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = self::_curl_exec($ch,$url,"" );
        curl_close($ch);
        return $output;
        
    }
    
    /**
     * POST 方式执行 HTTP请求
     * @param string	$url   完整url地址
     * @param mixed	$params	请求参数；string（k=v&k=v格式的字符串）；array('k'=>'v')形式的关联数组
     * @return string
     */    
    public static function post($url,$params,$header=array()){
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
        $output = self::_curl_exec($ch,$url,$params);
//	$curl_info = curl_getinfo($ch);
//	if( 1 ){
//	    var_dump($curl_info);
//	}
        curl_close($ch);
        return $output;
    }
    
    /**
     * 执行 curl 请求，如果失败，重试3次
     * @param object	$ch        curl 对象
     * @param string	$url       请求地址
     * @param mixed	$params     参数（string 或 数组）
     * @return mixed   string：成功，返回请求结果；false：失败
     */
    public static function _curl_exec( $ch,$url,$params){
        $i = 3;
        while( $i ){
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);// 设置超时
            $output = curl_exec($ch);
            if ( curl_errno($ch) == 0 ) {// 成功，直接返回
                return $output;
            }
            --$i;
        }
        if( !is_array( $params ) ){
            $arr = json_decode($params,true);
            if( $arr ){
                $params = $arr;
            }
        }
        return false;
    }
    
}
