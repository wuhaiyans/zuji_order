<?php
namespace App\Order\Modules\Inc;

class OrderInstalmentStatus{

    /**
     * @var int 未支付
     */
    const UNPAID = 1;
    /**
     * @var int 已支付
     */
    const SUCCESS = 2;
    /**
     * @var int 支付失败
     */
    const FAIL = 3;
    /**
     * @var int 已取消
     */
    const CANCEL = 4;

    /**
     * @var int 支付中
     */
    const PAYING = 5;

    /**
     * 获取代扣协议状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
            self::UNPAID => '未扣款',
            self::SUCCESS => '已扣款',
            self::FAIL => '扣款失败',
            self::CANCEL => '已取消',
            self::PAYING=>'支付中',
        ];
    }

    /**
     * 状态值 转换成 状态名称
     * @param int $status   状态值
     * @return string 状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}

