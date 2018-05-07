<?php
/**
 * 信用认证
 */

namespace App\Lib;


abstract class Certification{

    /**
     * @var string 芝麻认证平台编码
     */
    const ZhimaCode = 'ZHIMA';
    /**
     * @var string 京东小白信用认证平台编码
     */
    const JdxbxyCode = 'JDXIAOBAI';
    /**
     * @var int 芝麻认证平台值
     */
    const Zhima = 1;
    /**
     * 芝麻小程序认证
     */
    const ZhimaMini = 2;
    /**
     * 京东小白信用认证
     */
    const JdXiaoBai = 3;
    
    /**
     * 获取支持的认证平台列表
     * @return array  认证平台列表
     */
    public static function getPlatformList(){
        return [
            self::Zhima => '芝麻认证',
            self::ZhimaMini => '芝麻小程序认证',
            self::JdXiaoBai => '京东小白信用认证',
        ];
    }

    /**
     * 认证平台值 转成 认证平台名称
     * @param int   $platform   认证平台值
     * @return string   认证平台名称
     */
    public static function getPlatformName($platform){
        $list = self::getPlatformList();
        if( isset($list[$platform])){
            return $list[$platform];
        }
        return '';
    }

    /**
     * 判断认证平台值是否正确
     * @param int $platform 认证平台平台
     * @return bool     true：正确；false：不正确
     */
    public static function verifyPlatform($platform){
        $list = self::getPlatformList();
        if( isset($list[$platform])){
            return true;
        }
        return false;
    }


    public static function createPlatform( $platform ){
        if( !self::verifyPlatform($platform) ){
            return false;
        }

        switch ($platform){
            case 1:
                return new Zhima();
                break;
        }
    }

    /**
     * 信用分映射，平台相关的信用值 转换成 1-999之间的数
     * @param $credit_decoded
     * @return return int
     */
    abstract public static function creditEncode($credit_decoded);

    /**
     * 信用分映射，1-999之间的数 转换成 平台相关的信用值
     * @param $credit_decoded
     * @return return int
     */
    abstract public static function creditDecode($credit_encoded);


}