<?php
namespace App\Lib\Certificate;

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
     * 获取 APP对应的 密钥
     */
    static public function getAppSecret($appid=1):array{

        return [
            'secret_key' => 'd52uiuxj1y4flaky6z9y8lgz30i0d79q',
            'server_pub_key' => self::getPublicKeyFromX509(config('rsa.platform_public_key')),
            'server_pri_key' => self::getPrivateKeyFromX509(config('rsa.platform_private_key')),
            'client_pub_key' => self::getPublicKeyFromX509(config('rsa.platform_public_key')),
        ];
    }

    /**
     * 转换公钥格式
     * @param string $certificate 公钥字符串
     * @return string
     */
    public static function getPublicKeyFromX509(string $certificate):string {
        $publicKeyString = "-----BEGIN PUBLIC KEY-----\n".
            \wordwrap($certificate, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        return $publicKeyString;
    }

    /**
     * 转换私钥格式
     * @param string $certificate 私钥字符串
     * @return string
     */
    public static function getPrivateKeyFromX509(string $certificate):string {
        $publicKeyString = "-----BEGIN RSA PRIVATE KEY-----\n".
            \wordwrap($certificate, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        return $publicKeyString;
    }


    /**
     * 验签
     * @param string $data 待验证字符串
     * @param string $sign 字符串签名
     * @return bool true：成功；false：失败
     */
    static public function verifySign( array $data ){
        $sign = $data['sign'];
        $sign_type = $data['sign_type'];
        unset($data['sign']);
        ksort($data);
        $str = http_build_query($data);
        $secret_info = self::getAppSecret();
        if( $sign_type == 'RSA' ){
            $rsa_decrypt = new \App\Lib\Certificate\rsa\RSADecrypter(1024, $secret_info['server_pri_key'], $secret_info['client_pub_key']);
            $result = $rsa_decrypt->verify($str, $sign);
            return $result;
        }
        elseif ( $sign_type == 'MD5' ){
            return md5($str.$secret_info['secret_key']) == $sign;
        }
        // 第一期，没有指定 sign_type 不做校验，第二期上线后，必须指定一种签名模式
        return true;
    }


    /**
     * 生成签名
     * @param array $value
     * @return string
     */
    static public function generateSign( array $value ):string{
        $sign_type = $value['sign_type'];
        ksort($value);
        $str = http_build_query($value);
        $secret_info = self::getAppSecret();

        if( $sign_type == 'RSA' ){
            $rsa_encrypt = new \App\Lib\Certificate\rsa\RSAEncrypter(1024, $secret_info['client_pub_key'], $secret_info['server_pri_key']);
            $sign = $rsa_encrypt->sign( $str );
            return $sign;
        }
        elseif ( $sign_type == 'MD5' ){
            return md5($str.$secret_info['secret_key']);
        }
        return '';
    }
}