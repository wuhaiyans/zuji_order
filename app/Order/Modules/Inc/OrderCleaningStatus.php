<?php
/**
 * Created by PhpStorm.
 * User: heaven
 * Date: 2018/5/11
 * Time: 11:26
 */
namespace App\Order\Modules\Inc;

class OrderCleaningStatus{

    /******************* start  businessType 业务类型  2，退货 3 退款，4，还机 ，5，买断 ****************************************************/

    const businessTypeReturn = 2;

    const businessTypeRefund = 3;

    const businessTypeReturnGoods = 4;

    const businessTypeBuy  = 5;

/**************************end ************************************************************************/



/**************************************start 扣除押金状态；1：已取消；2：待扣押金；3：已扣押金；4：无需扣押金*************************************************/

    const depositDeductionStatusCancel = 1;

    const depositDeductionStatusUnpayed = 2;

    const depositDeductionStatusPayd = 3;

    const depositDeductionStatusNoPay = 4;

/***************************************end  *****************************************************************************/


/**************************************start 退还押金状态；1：已取消；2：待退还押金；3：已退还押金；4：无需退还*************************************************/

    const depositUnfreezeStatusCancel = 1;

    const depositUnfreezeStatusUnpayed = 2;

    const depositUnfreezeStatusPayd = 3;

    const depositUnfreezeStatusNoPay = 4;

/***************************************end  *****************************************************************************/


    /**************************************start 退款状态 1：已取消；2：待退款；3：已退款；4：无需退款*************************************************/

    const refundCancel = 1;

    const refundUnpayed = 2;

    const refundPayd = 3;

    const refundNoPay = 4;

    /***************************************end  *****************************************************************************/


    /**************************************start 清算的状态 1：已取消；2：待扣押金；3：待退还押金；4：待退款；5：出账中；6：清算已完成*************************************************/

    const orderCleaningCancel = 1;

    const orderCleaningDeposit = 2;

    const orderCleaningUnfreeze = 3;

    const orderCleaningUnRefund = 4;

    const orderCleaning= 5;

    const orderCleaningComplete= 6;

    /***************************************end  *****************************************************************************/




    /**
     * 业务类型列表
     * Author: heaven
     * @return array
     */
    /******************* start  businessType 业务类型  2，退货 3 退款，4，还机 ，5，买断 ****************************************************/

    public static function getBusinessTypeList(){
        return [
            self::businessTypeReturn => '退货',
            self::businessTypeRefund => '退款',
            self::businessTypeReturnGoods => '还机',
            self::businessTypeBuy => '买断',
        ];
    }



    /**
     * 业务类型值 转换成 状态名称
     * Author: heaven
     * @param $status 业务类型值
     * @return mixed|string  业务类型名称
     */
    public static function getBusinessTypeName($status){
        $list = self::getBusinessTypeList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }




    /**
     * 索赔状态列表
     * Author: heaven
     * @return array 索赔状态列表
     *
     */
    public static function getClaimStatusList(){
        return [
            self::claimStatus => '无效',
            self::claimStatusUnPayd => '待索赔支付',
            self::claimStatusPayd => '已索赔支付',
            self::claimStatusNoPay => '无索赔支付',
        ];
    }



    /**
     * 索赔状态名称
     * Author: heaven
     * @param $status
     * @return mixed|string
     */
    public static function getClaimStatusName($status){
        $list = self::getClaimStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }




    /**
     * 扣除押金状态列表
     * Author: heaven
     * @return array    扣除押金状态列表
     */
    public static function getDepositDeductionStatusList(){
        return [
            self::depositDeductionStatusCancel => '已取消',
            self::depositDeductionStatusUnpayed => '待扣押金',
            self::depositDeductionStatusPayd => '已扣押金',
            self::depositDeductionStatusNoPay => '无需扣押金',
        ];
    }



    /**
     * 扣除押金状态名称
     * Author: heaven
     * @param $status   扣除押金状态值
     * @return mixed|string 扣除押金状态名称
     */
    public static function getDepositDeductionStatusName($status){
        $list = self::getDepositDeductionStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }





    /**
     * 退还押金状态列表
     * Author: heaven
     * @return array    退还押金状态列表
     */
    public static function getDepositUnfreezeStatusList(){
        return [
            self::depositUnfreezeStatusCancel => '已取消',
            self::depositUnfreezeStatusUnpayed => '待退还押金',
            self::depositUnfreezeStatusPayd => '已退还押金',
            self::depositUnfreezeStatusNoPay => '无需退还',
        ];
    }


    /**
     * 退还押金状态名称
     * Author: heaven
     * @param int $status   退还押金状态值
     * @return string 退还押金状态名称
     */
    public static function getDepositUnfreezeStatusName($status){
        $list = self::getDepositUnfreezeStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }



    /**
     * 退款状态列表
     * Author: heaven
     * @return array    退款状态列表
     */

    public static function getRefundList(){
        return [
            self::refundCancel => '已取消',
            self::refundUnpayed => '待退款',
            self::refundPayd => '已退款',
            self::refundNoPay => '无需退款',
        ];
    }


    /**
     * 退款状态名称
     * Author: heaven
     * @param int $status   退款状态值
     * @return string 退款状态名称
     */
    public static function getRefundName($status){
        $list = self::getRefundList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }





    /**
     * 清算的状态列表
     * Author: heaven
     * @return array    清算的状态列表
     */
    public static function getOrderCleaningList(){
        return [
            self::orderCleaningCancel => '已取消',
            self::orderCleaningDeposit => '待扣押金',
            self::orderCleaningUnfreeze => '待退还押金',
            self::orderCleaningUnRefund => '待退款',
            self::orderCleaning => '出账中',
            self::orderCleaningComplete => '已出账',
        ];
    }


    /**
     * 清算的状态名称
     * Author: heaven
     * @param int $status   清算的状态值
     * @return string 清算的状态名称
     */
    public static function getOrderCleaningName($status){
        $list = self::getOrderCleaningList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }






}

