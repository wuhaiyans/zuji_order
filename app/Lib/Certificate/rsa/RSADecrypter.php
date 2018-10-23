<?php
namespace App\Lib\Certificate\rsa;
/**
 * RSADecrypter
 * RSA 算法解密
 * @author liuhongxing
 */
class RSADecrypter implements \App\Lib\Certificate\Decrypter {
    
    private $privateKey = '';
    private $publicKey = '';
    
    private $keyBits = 1024;    // 秘钥长度（位）
    private $maxLength = 128;   //最大加密字节长度，max number of chars (bytes) to encrypt;
    /**
     * 
     * @param string $privateKey    客户端私钥，用于解密
     * @param string $publicKey     服务端公钥，用于校验签名
     */
    public function __construct( $keyBits, $privateKey, $publicKey ) {
        $this->keyBits = intval($keyBits);
        $this->maxLength = intval($this->keyBits/8);
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }
    
    public function decrypt( string $text ):string {
        // 私钥
        $privateKey = $this->privateKey;
        
        $text = base64_decode($text);
        
        $max_length = $this->maxLength;

        $blen = strlen($text);
        $tlen = floor($blen / $max_length);
        $llen = $blen % $max_length;


        $decrypted_all = "";

        for($i = 0; $i<$tlen; $i++) {
            $ret = openssl_private_decrypt(substr($text, $i*$max_length, $max_length), $decrypted, $privateKey);
            if($ret==false){
                $msg = 'openssl_private_decrypt(): '.  openssl_error_string();exit;
                //Debug::error($msg);
            }
            if($ret)
                $decrypted_all .= $decrypted;
        }

        if($llen > 0) {
            $ret = \openssl_private_decrypt(substr($text, $tlen*$max_length, $llen), $decrypted, $privateKey);
            if($ret)
                $decrypted_all .= $decrypted;
        }


        return $decrypted_all;
    }

    function verify(string $data, string $sign):bool {

        $res = '';
        if (!$this->checkEmpty($this->publicKey)) {
            $pubKey = $this->publicKey;
            $res = openssl_get_publickey($pubKey);
        }

        if(!$res){
            return false;
        }

        //调用openssl内置方法验签，返回bool值
        $result = (bool) openssl_verify($data, base64_decode($sign), $res);

        if (!$this->checkEmpty($this->publicKey)) {
            //释放资源
            openssl_free_key($res);
        }

        return $result;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     * */
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }
    
}
