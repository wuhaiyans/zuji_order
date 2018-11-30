<?php
/**
 * Curl 封装
 * @access public
 * @author gaobo
 * @editor gaobo
 * @copyright (c) 2017, Huishoubao
 * 
 */
namespace App\Lib;
use App\Lib\Common\LogApi;

/**
 * Curl类
 *
 * @access public
 * @author gaobo
 * @editor gaobo
 */
class CurlMulti {
    
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
     * 错误提示
     * @var array
     */
    private static $errors = [];
    
    /**
     * 错误提示
     * @var array
     */
    private static $errorUrlArr = [];
    
    /**
     * 错误提示
     * @var array
     */
    private static $outputs = [];
    
    /**
     * 超时时间
     * @var int
     */
    public static $timeout = 60;
    
    /**
     *
     * @var type
     */
    public static $curl_infos = null;
    
    
    /**
     * 判断执行是否
     * @access public
     * @author gaobo
     * @return bool
     */
    public static function hasError():bool{
        return self::$errno>0;
    }
    /**
     *
     * @access public
     * @author gaobo
     * @return string
     */
    public static function getError():array{
        return self::$errors;
    }
    
    /**
     *
     * @access public
     * @author gaobo
     * @return array
     */
    public static function getErrorUrlArr():array{
        return self::$errorUrlArr;
    }
    
    /**
     *
     * @access public
     * @author gaobo
     * @return array
     */
    public static function getOutputs():array{
        return self::$outputs;
    }
    
    /**
     * 请求具体内容
     * curl_infos() 的结果
     * @access public
     * @author gaobo
     * @return array
     */
    public static function getInfos():array{
        return self::$curl_infos;
    }
    /**
     *
     * @access public
     * @author gaobo
     * @param array $data
     * @return string
     */
    public static function build_query(array $data):string{
        return http_build_query( $data);
    }
    
    /**
     * GET 方式执行 HTTP请求
     * @access public
     * @author gaobo
     * @param array	$urls   ['完整URL1','完整URL2','完整URLX',...]
     * @return array
     */
    public static function get(array $urls){
        $output = self::_send($urls);
        return $output;
    }
    
    /**
     * POST 方式执行 HTTP请求
     * @access public
     * @author gaobo
     * @param array	$multi_infos   [['url'=>'完整URL','params'=>[xxx],'header'=>[xxx]]]
     * @param mixed		$multi_infos['params']	请求参数；string（k=v&k=v格式的字符串）；array('k'=>'v')形式的关联数组
     * @return string
     */
    public static function post(array $multi_infos){
        if($multi_infos){
            foreach($multi_infos as $key => $multi_info){
                if($multi_info['url'] && isset($multi_info['params']) && isset($multi_info['header'])){
                    if(is_array($multi_info['params']) ){
                        $multi_infos[$key]['params'] = http_build_query($multi_info['params']);
                    }
                }
            }
            if(!empty($multi_infos)){
                self::_send($multi_infos);
            }
            return self::$outputs;
        }
        self::$error = 'Bad request';
        return false;
    }
    
    /**
     *
     * @access public
     * @author gaobo
     * @param array	$multi_infos   [['url'=>'完整URL','params'=>[xxx],'header'=>[xxx]]]
     * @param string $multi_infos['url']		请求地址
     * @param array $multi_infos['params']		请求参数 array('k'=>'v')形式的关联数组
     * @param array $multi_infos['header']		请求头 array('k'=>'v')形式的关联数组
     * @return string
     */
    private static function _send($multi_infos){
        $chs = [];
        $mh = curl_multi_init();
        foreach($multi_infos as $key => $multi_info){
            $chs[$key] = curl_init();
            curl_setopt($chs[$key], CURLOPT_URL, $multi_info['url']);
            curl_setopt($chs[$key], CURLOPT_RETURNTRANSFER, 1);
            if($multi_info['params']){
                // post数据
                curl_setopt($chs[$key], CURLOPT_POST, true);
                // post的变量
                curl_setopt($chs[$key], CURLOPT_POSTFIELDS, $multi_info['params']);
            }
            // https请求不验证证书和hosts
            curl_setopt($chs[$key], CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chs[$key], CURLOPT_SSL_VERIFYHOST, false);
            // header 头参数
            if(isset($multi_info['header'])){
                curl_setopt($chs[$key], CURLOPT_HTTPHEADER, $multi_info['header']);
            }
            
            //至关重要，CURLINFO_HEADER_OUT选项可以拿到请求头信息
            curl_setopt($chs[$key], CURLINFO_HEADER_OUT, TRUE);
            // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
            curl_setopt($chs[$key], CURLOPT_HEADER, false);
            curl_setopt($chs[$key], CURLOPT_TIMEOUT, self::$timeout);// 设置超时
            curl_multi_add_handle($mh, $chs[$key]); //决定exec输出顺序
        }
        
        $running = null;
        do { //执行批处理句柄
            curl_multi_exec($mh, $running); //CURLOPT_RETURNTRANSFER如果为0,这里会直接输出获取到的内容.如果为1,后面可以用curl_multi_getcontent获取内容.
            curl_multi_select($mh); //阻塞直到cURL批处理连接中有活动连接,不加这个会导致CPU负载超过90%.
        } while ($running > 0);
        
        foreach($chs as $key => $ch) {
            self::$curl_infos[$key] = curl_getinfo($ch);
            $errno= curl_error($ch);
            if(!$errno){//成功
                self::$outputs[$key] = curl_multi_getcontent($ch);
            }else{
                self::$errors[$key] = curl_error($ch);
                self::$errno = !self::$errno && $errno;
            }
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);
    }
}

