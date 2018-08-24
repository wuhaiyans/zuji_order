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

class Channel  extends \App\Lib\BaseApi{

    /**
     * 获取渠道所有信息
     * @author wuhaiyan
     * @param $appid  //【必须】string appid
     * @return array
     * @throws \Exception			请求失败时抛出异常
     */
    public static function getChannel($appid){

        return self::request(\config('app.APPID'), \config('goodssystem.GOODS_API'),'zuji.goods.channel.appid.get', '1.0', ['appid'=>$appid]);
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

            $channerName[$values['id']] = $values['name'];
        }
        return $channerName;


    }

    /**
     *
     * 获取渠道应用列表
     * Author: limin
     * @return mixed|string
     */
    public static function getChannelAppidListName()
    {
        $data = config('tripartite.Interior_Goods_Request_data');
        $data['method'] ='zuji.channel.appid.list.get';
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

            $channerName[$values['id']] = $values['name'];
        }
        return $channerName;


    }
}



















