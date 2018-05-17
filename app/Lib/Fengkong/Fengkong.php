<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Lib\Fengkong;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Fengkong{


    public static function getYidun($data,$arr){
        $data['method'] ='yidun.get.user.yiduninfo';
        $data['params'] = [
            'member_id'=>$arr['user_id'],
            'user_name'=>$arr['user_name'],
            'cert_no'=>$arr['cert_no'],
            'mobile'=>$arr['mobile'],
        ];
        //var_dump($data);die;
        $info = Curl::post(config('tripartite.Interior_Fengkong_Url'), json_encode($data));
        $info =json_decode($info,true);
        //var_dump($info);die;
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
    public static function getCredit($data,$arr){
        $data['method'] ='system.get.risk.score';
        $data['params'] = [
            'member_id'=>$arr['user_id'],
        ];
        //var_dump($data);die;
        $info = Curl::post(config('tripartite.Interior_Fengkong_Url'), json_encode($data));
        $info =json_decode($info,true);
        //var_dump($info);die;
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }




}



















