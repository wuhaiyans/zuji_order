<?php
namespace app\Lib\Certificate;

/**
 * Decrypter
 * 解密器
 * @author liuhongxing
 */
interface Decrypter {
    
    /**
     * 解密字符串
     * @param string    $data       待解密字符串
     * @return string   解密结果字符串
     */
    public function decrypt( string $data ):string;
    
    /**
     * 检验签名
     * @param string $data  加密字符串
     * @param string $signature  签名字符串
     * @return boolean  true: the signature is correct；false: it is incorrect
     */
    public function verify( string $data, string $signature ):bool;
    
}
