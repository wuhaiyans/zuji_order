<?php
/**
 *  下单第三方接口调用类
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/5/2
 * Time: 16:32
 */

namespace App\Lib\Channel;
use App\Lib\ApiStatus;
use App\Lib\Curl;

class Channel{

    /**
     * 获取渠道信息
     * @param $appid
     * @return string or array
     */
    public static function getChannel($appid){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.channel.appid.get';
        $data['params'] = [
            'appid'=>$appid,
        ];

        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);

        //var_dump($info);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data']['appid'];
    }

    /**
     * 获取渠道信息
     * @param $appid
     * @return string or array
     */
    public static function getAllChannel($appid){
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.goods.channel.appid.get';
        $data['params'] = [
            'appid'=>$appid,
        ];

        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);

        //var_dump($info);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return $info['code'];
        }
        return $info['data'];
    }



    /**
     *
     * 获取渠道列表
     * Author: heaven
     * @return mixed|string
     */
    public static function getChannelListName()
    {
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.channel.list.get';
        $data['params'] = [
        ];
        $info = Curl::post(config('tripartite.Interior_Goods_Url'), json_encode($data));
        $info =json_decode($info,true);
        //var_dump($info);
        if(!is_array($info)){
            return ApiStatus::CODE_60000;
        }
        if($info['code']!=0){
            return false;
        }
        foreach($info['data'] as $keys=>$values) {

            $channerName[] = $values['name'];
        }
        return $channerName;


    }


}



















