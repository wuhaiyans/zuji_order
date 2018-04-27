<?php

/**
 * 数字签名工具
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

/**
 * API 工具类
 * @access public 
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class ApiUtil{
    
    
    /**
     * 数组转字符串
     * @param array    $arr   【必须】数组
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @return stirng	字符串
     */
    public static function arr2str($arr) {
	
    }
    
    /**
     * 签名
     * @param stirng    $str   【必须】待签名字符串
     * @access public 
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @return stirng	字符串签名
     */
    public static function md5($str) {
	//-+--------------------------------------------------------------------
	// | 接收请求（直接使用$_GET或$_POST接收时，可以省略）
	//-+--------------------------------------------------------------------
	// todo
	
	//-+--------------------------------------------------------------------
	// | 参数校验（必须）
	//-+--------------------------------------------------------------------
	// todo
	
	//-+--------------------------------------------------------------------
	// | 业务访问限制（无业务限制时，可以省略）
	//-+--------------------------------------------------------------------
	// todo
	
	//-+--------------------------------------------------------------------
	// | 业务处理（根据请求参数，进行相应的业务处理）
	//-+--------------------------------------------------------------------
	// todo（调用业务相关的接口，进行逻辑组合）
	// 1）（做什么，获取什么值）
	// 2）（做什么，获取什么值）
	// 3）（做什么，获取什么值）
	// ...
	// 4）（获取到业务处理最后结果）
	
	//-+--------------------------------------------------------------------
	// | 业务结果返回
	//-+--------------------------------------------------------------------
	// todo （返回结果）(如果需要，就对业务处理结果进行格式化处理)
    }
    
    /**
     * 验签
     * @param string $data	待验证字符串
     * @param string $sign	字符串签名
     * @param mixed  $res	验证签名相关资源
     * @return boolean	true：成功；false：失败
     */
    public static function verify($data,$sign,$res){
        return true;
    }

    /**
     * RSA验签
     * @param string $data 待验证字符串
     * @param string $sign 字符串签名
     * @return bool true：成功；false：失败
     */
    public static function rsa_verify($data, $sign){
        $request = api_request();
        $appid = $request->getAppid();
        $Redis = \zuji\cache\Redis::getInstans();
        $info = $Redis->hget('channel:appid', $appid);
        $appid_arr = json_decode($info, true);
        $rsa_decrypt = new \zuji\certificate\rsa\RSADecrypter(1024, $appid_arr['platform_private_key'], $appid_arr['client_public_key']);
        $result = $rsa_decrypt->verify($data, $sign);
        return $result;
    }
    
}
