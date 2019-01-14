<?php
namespace App\Order\Modules\Inc;

class OrderOverdueStatus{

    /**
     * @var int 未扣款
     */
    const UNPAID = 1;

    /**
     * @var int 扣款成功
     */
    const SUCCESS = 2;

    /**
     * @var int 扣款中
     */
    const PAYING = 3;

    /*
     * 逾期记录状态
     */
    const EFFECTIVE = 0;  //有效
    const INVALID   = 1;  //无效

    /**
     * 获取逾期扣款状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
            self::UNPAID    => '未扣款',
            self::SUCCESS   => '扣款成功',
            self::PAYING    => '扣款中',
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
    /**
     * 获取逾期记录状态列表
     * @return array
     */
    public static function getOverdueStatusList(){
        return [
            self::EFFECTIVE    => '有效',
            self::INVALID      => '无效',
        ];
    }

    /**
     * 状态值 转换成 状态名称
     * @param int $status   状态值
     * @return string 状态名称
     */
    public static function getOverdueStatusName($status){
        $list = self::getOverdueStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }

}

