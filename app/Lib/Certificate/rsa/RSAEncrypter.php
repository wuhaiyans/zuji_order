<?php
namespace App\Lib\Certificate\rsa;

/**
 * RSAEncrypter
 * RSA 算法加密
 * @author liuhongxing
 */
class RSAEncrypter implements \App\Lib\Certificate\Encrypter {
    
    private $publicKey = '';
    private $privateKey = '';
    
    private $keyBits = 1024;    // 秘钥长度（位）
    private $maxLength = 117;   //最大加密字节长度，max number of chars (bytes) to encrypt;
    
    /**
     * 
     * @param string $publicKey     客户端公钥，用于数据加密
     * @param string $privateKey    服务端私钥，用于加密数据签名
     */
    public function __construct( $keyBits, $publicKey, $privateKey ) {
        $this->keyBits = intval($keyBits);
        $this->maxLength = intval($this->keyBits/8-11);
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        
    }
    
	/**
	 * 加密
	 * @param string $text	明文字符串
	 * @return string	加密字符串
	 */
    public function encrypt( string $text ): string{
        // 公钥
        $publicKey = $this->publicKey;
        
        // 最大长度
        $max_length = $this->maxLength;
        
        // 字符串长度
        $blen = strlen($text);
        //
        $tlen = floor($blen / $max_length);
        $llen = $blen % $max_length;
        
        $encrypted_all = "";

        for($i = 0; $i<$tlen; $i++) {
            $ret = openssl_public_encrypt(substr($text, $i*$max_length, $max_length), $encrypted, $publicKey);
            if($ret)
                $encrypted_all .= $encrypted;
        }

        if($llen > 0) {
            $ret = openssl_public_encrypt(substr($text, $tlen*$max_length, $llen), $encrypted, $publicKey);
            if($ret)
                $encrypted_all .= $encrypted;
        }

        return base64_encode($encrypted_all);
    }
    
	/**
	 * 签名
	 * @param string $text	待签名字符串
	 * @return string	签名字符串
	 */
    public function sign( string $text ):string{
        $res = openssl_get_privatekey($this->privateKey);
        // $data=sha1($data); //sha1加密（如果需要的话，如果进行加密，则对方也要进行加密后做对比）
        openssl_sign($text, $sign, $res);//加签
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }
}
