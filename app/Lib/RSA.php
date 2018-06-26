<?php
namespace App\Lib;

// 解决PHP5.4.0以前gzdecode()不存在的问题
function _gzdecode($data)
{
    if( !function_exists('\gzdecode') ){
        return \gzinflate(substr($data,10,-8));
    }else{
        return \gzdecode($data);
    }
   
} 

class RSA{
    
    
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
	 * 解密数据
	 * @param string $encrypted		密文字符串
	 * @param string $private_key	解密私钥字符串（格式化的）
	 * @return string				解密明文字符串
	 * @throws \Exception
	 */
    public static function decodeData( string $encrypted, string $private_key):string {

        $pi_key =  \openssl_pkey_get_private($private_key);
		if( !$pi_key ){
			throw new \Exception(openssl_error_string());
		}

        $b64 = \base64_decode($encrypted);

        $max_length = 128;

        $blen = \strlen($b64);
        $tlen = \floor($blen / $max_length);
        $llen = $blen % $max_length;

        $decrypted_all = "";

        for($i = 0; $i<$tlen; $i++) {
            $ret = \openssl_private_decrypt(\substr($b64, $i*$max_length, $max_length), $decrypted, $pi_key);
            if($ret)
                $decrypted_all .= $decrypted;
        }

        if($llen > 0) {
            $ret = \openssl_private_decrypt(\substr($b64, $tlen*$max_length, $llen), $decrypted, $pi_key);
            if($ret)
                $decrypted_all .= $decrypted;
        }


        return $decrypted_all;
    }

    /**
	 * 解密并解压数据
	 * @param string $encrypted		密文字符串
	 * @param string $private_key	解密私钥字符串（格式化的）
	 * @return string	明文字符串
	 * @return string
	 */
    public static function decodeGzData(string $encrypted, string $private_key):string {

        $decrypted_all = self::decodeData($encrypted, $private_key);

        $text = _gzdecode($decrypted_all);

        return $text;
    }

    /**
	 * 加密数据
	 * @param string $text		明文字符串
	 * @param string $public_key	加密公钥字符串（格式化的）
	 * @return string
	 */
    public static function encodeData(string $text, string $public_key):string {
        $pu_key = \openssl_pkey_get_public($public_key);

        $max_length = 117;

        $blen = \strlen($text);
        $tlen = \floor($blen / $max_length);
        $llen = $blen % $max_length;
        
        $encrypted_all = "";

        for($i = 0; $i<$tlen; $i++) {
            $ret = \openssl_public_encrypt(substr($text, $i*$max_length, $max_length), $encrypted, $pu_key);
            if($ret)
                $encrypted_all .= $encrypted;
        }

        if($llen > 0) {
            $ret = \openssl_public_encrypt(substr($text, $tlen*$max_length, $llen), $encrypted, $pu_key);
            if($ret)
                $encrypted_all .= $encrypted;
        }

        $b64 = \base64_encode($encrypted_all);

        return $b64;
    }

	/**
	 * 压缩并加密服务端数据
	 * @param string $text		明文字符串
	 * @param string $public_key	加密公钥
	 * @return string
	 */
    public static function encodeGzData(string $text, string $public_key):string {
        $gzip = \gzencode($text);
        return self::encodeData($gzip, $public_key);
    }
	
    /**
	 * 加密服务端数据
	 * <p> 先 获取签名，再 进行 base64_encode() 处理</p>
	 * @param string $encrypted_all		待签名祝福词
	 * @param string $sign_key			签名私钥
	 * @return string					签名结果字符串
	 */
    public static function sign(string $encrypted_all, string $sign_key):string {
        $ps_key = \openssl_pkey_get_private($sign_key);
        \openssl_sign($encrypted_all, $signature, $ps_key);
        $sign_b64 = \base64_encode($signature);
        return $sign_b64;
    }

	/**
	 * 验签
	 * <p> 先 进行 base64_decode()，再 做验签处理</p>
	 * @param string $data			待验签字符串
	 * @param string $signature		签名字符串
	 * @param string $public_key	验签公钥
	 * @return bool  验签结果；true：成功；false：失败
	 */
    public static function verify( string $data, string $signature, string $public_key ):bool{
		$signature = \base64_decode( $signature );
        $ok = \openssl_verify($data, $signature, $public_key);
        if ($ok == 1) {
            return true;
        } 
        return false;
    }
}