<?php

namespace App\Order\Modules\Inc;

class OrderRiskCheckStatus{

    //--------------------------------------------------------------------------------------------
    //--+ 订单风控审核状态
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 默认,不需要使用
     */
    const Non = 0;
    /**
     * @var int 1，系统通过；
     */
    const SystemPass = 1;
    /**
     * @var int 2，建议复核；
     */
    const ProposeReview = 2;
    /**
     * @var int 3，复核通过；
     */
    const ReviewPass  = 3;
    /**
     * @var int 4，复核拒绝
     */
    const ReviewReject = 4;

    //--------------------------------------------------------------------------------------------
    //--+ 订单风控审核状态 end ----------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * 订单风控审核状态列表
     * @return array    订单风控审核状态列表
     */
    public static function getStatusList(){
        return [
            self::Non => '无',
            self::SystemPass => '系统通过',
            self::ProposeReview => '建议复核',
            self::ReviewPass => '复核通过',
            self::ReviewReject => '复核拒绝',
        ];
    }

    /**
     * 订单风控审核状态 转换成 状态名称
     * @param int $status   订单状态值
     * @return string 订单风控审核状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}

