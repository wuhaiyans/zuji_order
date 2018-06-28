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
    const RiskYidun ="yidun"; //蚁盾
    const RiskKnight ='knight';//白骑士
    const RiskTongdun ='tongdun';//同盾
    const RistKnightJd ='knight_jd';//京东
    const RistKnightTaobao ='knight_taobao';//淘宝
    const RistKnightMno='knight_mno';//白骑士运营商


    //风控值描述

    const DecisionAccept = 'ACCEPT';
    const DecisionReject = 'REJECT';
    const DecisionValidate = 'VALIDATA';

    const DecisionTrue ='true';
    const DecisionFalse ='false';
    /**
     *  获取风控类型列表
     * @return array
     */
    public static function getRiskList(){
        return [
            self::RiskYidun => '蚁盾',
            self::RiskKnight => '白骑士',
            self::RiskTongdun => '同盾',
            self::RistKnightJd => '京东',
            self::RistKnightTaobao => '淘宝',
            self::RistKnightMno => '白骑士运营商',
        ];
    }
    /**
     *  根据风控类型转换 文字
     * @param int $decision 描述
     * @return string 描述值
     */
    public static function getRiskName($risk){
        $list = self::getRiskList();
        if( isset($list[$risk]) ){
            return $list[$risk];
        }
        return '';
    }

    /**
     *  获取风控值列表
     * @return array
     */
    public static function getDecisionList(){
        return [
            self::DecisionAccept => '低风险/接收',
            self::DecisionReject => '高风险/拒绝',
            self::DecisionValidate => '中风险/待审',

            self::DecisionTrue => '通过',
            self::DecisionFalse => '未通过',
        ];
    }
    /**
     *  根据风控值转换 文字
     * @param int $decision 描述
     * @return string 描述值
     */
    public static function getDecisionName($decision){
        $list = self::getDecisionList();
        if( isset($list[$decision]) ){
            return $list[$decision];
        }
        return '';
    }


    /**
     * 获取风控白骑士信息
     * @param $arr
     * [
     *      'user_id'=>'',//用户ID
     * ]
     * @return array
     */
    public static function getKnight($arr){
        $data=config('tripartite.Interior_Fengkong_Request_data');
        $data['method'] ='system.risk.info';
        $data['params'] = [
            'user_id'=>$arr['user_id'],
        ];
        $info = Curl::post(config('tripartite.Interior_Fengkong_Url'), $data);
        $info =json_decode($info,true);
        if(!is_array($info)){
            throw new \Exception("风控白骑士接口获取失败");
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }


    /**
     * 获取蚁盾信息
     * @param $arr
     * @return mixed|string
     */
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
            return "蚁盾接口请求失败";
        }
        if($info['code']!=0){
            return $info['msg'];
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



















