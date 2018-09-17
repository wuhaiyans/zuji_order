<?php
/**
 * 预约列表筛选配置
 * Author: qinliping
 * Email :qinliping@huishoubao.com.cn
 * Date: 2018/9/11
 * Time: 下午 2:38
 */
namespace App\Activity\Modules\Inc;
use App\Lib\Channel\Channel;
use App\Activity\Modules\Inc;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;

class OppointmentListFiler
{

    /**
     * 获取预约筛选项
     * Author: qinliping
     * @return array
     */
    private static function getOppointmentState()
    {
       $channlistName =  Channel::getChannelListName();
        return array(

                    'order_state'=>OrderStatus::getStatusType(),    //订单状态
                    'pay_type_list' =>PayInc::getOppointmentPayList(), //支付状态
                    'appid_list' => $channlistName,                      //应用渠道
                    'destine_list' =>DestineStatus::getStatusType()     //预约状态
                );
    }



    /**
     * 预约相关的配置信息
     * Author: qinliping
     * @param string $id 需要查找预约模块的key值
     * @param string $incName 验证预约模块名称
     * @return array|bool|mixed
     */
    public static function OppointmentInc($id='',$incName='') {
        //预约状态
        if (isset(self::getOppointmentState()[$incName])) {
            if ($id!=''){
                if (isset(self::getOppointmentState()[$incName][$id])) {
                    return self::getOppointmentState()[$incName][$id];
                } else {
                    return false;
                }

            } else {
                return self::getOppointmentState()[$incName];
            }
        } else {
            return self::getOppointmentState();
        }
    }













}
