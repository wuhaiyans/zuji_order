<?php
namespace App\Order\Modules\Inc;

class OrderInstalmentStatus{

    /*****************************start 分期的状态 1：未支付；2：已支付；3：支付失败；4：已取消；5：支付中 *********************************/
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

    /***************************************end  *****************************************************************************/

    /*****************************start 分期支付类型 0：未支付；1：已支付；2：支付失败； *********************************************/
    /**
     * @var int 代扣
     */
    const WITHHOLD = 0;
    /**
     * @var int 主动还款
     */
    const REPAYMENT = 1;
    /**
     * @var int 线下还款
     */
    const UNDERLINE = 2;


    /***************************************end  *****************************************************************************/


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

