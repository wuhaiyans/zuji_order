<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Lib\Risk;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Risk{

    //风控类型
    const RiskYidun ="yidun";


    public static function getRisk($arr){
        $data=config('tripartite.Interior_Fengkong_Request_data');
        $data['method'] ='yidun.get.user.yiduninfo';
        $data['params'] = [
            'member_id'=>$arr['user_id'],
            'user_name'=>$arr['user_name'],
            'cert_no'=>$arr['cert_no'],
            'mobile'=>$arr['mobile'],
            'channel_appid'=>$arr['channel_appid'],
        ];
        $info = Curl::post(config('tripartite.Interior_Fengkong_Url'), $data);
        $info =json_decode($info,true);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }

    /**
     * 获取风控系统的分数
     * @return int
     */
    public static function getCredit($arr){
        $data=config('tripartite.Interior_Fengkong_Request_data');
        $data['method'] ='system.get.risk.score';
        $data['params'] = [
            'member_id'=>$arr['user_id'],
        ];
        $info = Curl::post(config('tripartite.Interior_Fengkong_Url'), $data);
        $info =json_decode($info,true);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }


    /**
     * 将小程序风控数据传入风控系统
     * @return int
     */
    public static function setMiniRisk($arr){
        $data=config('tripartite.Interior_Fengkong_Request_data');
        $data['method'] ='zhima.mini.zhima';
        $data['params'] = $arr;
        $info = Curl::post(config('tripartite.Interior_Fengkong_Url'), $data);
        $info =json_decode($info,true);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return false;
        }
        return true;
    }

}



















