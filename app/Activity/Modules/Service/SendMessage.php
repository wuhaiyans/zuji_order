<?php
namespace App\Activity\Modules\Service;

use App\Lib\Common\LogApi;

class SendMessage{

    /**
     * 活动体验支付成功
     * @param array $params
     * [
     *      'mobile'        => '1', // 手机号
     * ]
     * @return bool true/false
     */
    public static function ExperienceDestineSuccess($params){

        $params = filter_array($params, [
            'mobile'        => 'required',
        ]);

        if( count( $params ) < 1 ){
            LogApi::error('[ExperienceDestine]短信发送参数异常',$params);
            return false;
        }

        $mobile = $params['mobile'];    // 手机号
        $code   = 'SMS_113461199';      // 短信模板

        // 短信参数  无参数 给个默认的
        $dataSms = [
            'newPhoneName'      => 'ExperienceDestine'
        ];
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($mobile, $code, $dataSms);
    }

    /**
     * 预约成功
     * @param array $params
     * [
     *      'mobile'        => '1', // 手机号
     *      'goods_name'    => '1', // 商品名称
     * ]
     * @return bool true/false
     */
    public static function ActivityDestineSuccess($params){

        $params = filter_array($params, [
            'mobile'        => 'required',
            'goods_name'    => 'required',
        ]);

        if( count( $params ) < 2 ){
            LogApi::error('[ActivityDestine]短信发送参数异常',$params);
            return false;
        }

        $mobile = $params['mobile'];    // 手机号
        $code   = 'SMS_113461063';      // 短信模板

        // 短信参数
        $dataSms = [
            'newPhoneName'      => $params['goods_name'],
        ];
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($mobile, $code, $dataSms);
    }


    /**
     * 已发货
     * @param array $params
     * [
     *      'mobile'            => '1', // 手机号
     *      'goods_name'        => '1', // 商品名称
     *      'logistics_no'      => '1', // 物流单号
     * ]
     * @return bool true/false
     */
    public static function AlreadyDeliver($params){

        $params = filter_array($params, [
            'mobile'            => 'required',
            'goods_name'        => 'required',
            'logistics_no'      => 'required',
        ]);

        if( count( $params ) < 3 ){
            LogApi::error('[AlreadyDeliver]短信发送参数异常',$params);
            return false;
        }

        $mobile = $params['mobile'];    // 手机号
        $code   = 'SMS_113461180';      // 短信模板

        // 短信参数
        $dataSms = [
            'newPhoneName'      => $params['goods_name'],
            'logisticsNo'       => $params['logistics_no'],
        ];
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($mobile, $code, $dataSms);
    }
}