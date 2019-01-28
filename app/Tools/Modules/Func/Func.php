<?php 
namespace App\Tools\Modules\Func;

class Func {
    
    /**
     * 手机号检测
     */
    public static function checkMobileValidity($mobilephone){
        $exp = "/^1[0-9]{1}[0-9]{1}[0-9]{8}$|15[012356789]{1}[0-9]{8}$|18[012356789]{1}[0-9]{8}$|14[57]{1}[0-9]$/";
        if(preg_match($exp,$mobilephone)){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 获取16位 md5
     */
    public static function md5_16(){
        return substr(md5(self::uuid()),8,16);
    }
    private static function uuid($prefix = '')
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars,0,8);
        $uuid .= substr($chars,8,4);
        $uuid .= substr($chars,12,4);
        $uuid .= substr($chars,16,4);
        $uuid .= substr($chars,20,12);
        return $prefix . $uuid;
    }
    
}