<?php

namespace App\Lib\Tool;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Tool extends \App\Lib\BaseApi{

    public static function getIsMember($mobile){
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.tool.getIsMember', '1.0', ['mobile'=>$mobile]);

    }
    
    public static function getSku($sku_id){
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.tool.getSku', '1.0', ['sku_id'=>$sku_id]);
        
    }
    
    public static function getSpu($spu_id){
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.tool.getSpu', '1.0', ['spu_id'=>$spu_id]);
        
    }
    
    public static function getChannel($where , $field = 'name'){
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.tool.getChannel', '1.0', ['where'=>$where,'field'=>$field]);
        
    }
    
    public static function getSpuNames($where){
        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.tool.getSpuNames', '1.0', ['where'=>$where]);
        
    }
}



















