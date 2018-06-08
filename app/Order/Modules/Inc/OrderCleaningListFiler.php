<?php
/**
 * 订单清算列表筛选配置
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/6/05 0018
 * Time: 下午 2:38
 */
namespace App\Order\Modules\Inc;
use App\Lib\Channel\Channel;
use App\Order\Modules\Inc;
class OrderCleaningListFiler
{

    /**
     * 获取订单清算筛选项
     * Author: heaven
     * @return array
     */
    private static function getOrderCleanState()
    {
       $channlistName =  Channel::getChannelListName();
        return array(
                    //出账类型
                    'out_status'=>Inc\OrderCleaningStatus::getOrderCleaningList(),
                    //出账类型
                    'out_type'=>array(
                            'zujin' => '租金',
                            'other' => '其它',
                        ),
                    //入账来源
                    'appid_list' => $channlistName,
                    //出账方式
                    'pay_type_list' =>Inc\PayInc::getPayList(),
                );
    }



    /**
     * 订单清算相关的配置信息
     * Author: heaven
     * @param string $id 需要查找订单模块的key值
     * @param string $incName 验证订单模块名称
     * @return array|bool|mixed
     */
    public static function orderCleanInc($id='',$incName='') {
        //订单状态
        if (isset(self::getOrderCleanState()[$incName])) {
            if ($id!=''){
                if (isset(self::getOrderCleanState()[$incName][$id])) {
                    return self::getOrderCleanState()[$incName][$id];
                } else {
                    return false;
                }

            } else {
                return self::getOrderCleanState()[$incName];
            }
        } else {
            return self::getOrderCleanState();
        }
    }













}
