<?php
/**
 * Created by PhpStorm.
 * User: heaven
 * Date: 2018/5/11
 * Time: 11:26
 */
namespace App\Order\Modules\Inc;

class OrderCleaningStatus{

    /******************* start  businessType 业务类型 1.退货，2，退款，3，还机 ，4，买断，5续租，6换货 ****************************************************/

    const businessTypeReturn = 1;

    const businessTypeRefund = 2;

    const businessTypeReturnGoods = 3;

    const businessTypeBuy  = 4;

    const businessTypeRent = 5;

    const businessTypeExchangeGoods = 6;

/**************************end ************************************************************************/

/**************************************start claim_status  索赔状态 0：无效；1：待索赔支付；2：已索赔支付；3：无索赔支付*************************************************/

    const claimStatus = 1;

    const claimStatusUnPayd = 2;

    const claimStatusPayd = 3;

    const claimStatusNoPay = 4;

/***************************************end  *****************************************************************************/


/**************************************start 扣除押金状态；0：已取消；1：待扣押金；2：已扣押金；3：无需扣押金*************************************************/

    const depositDeductionStatusCancel = 1;

    const depositDeductionStatusUnpayed = 2;

    const depositDeductionStatusPayd = 3;

    const depositDeductionStatusNoPay = 4;

/***************************************end  *****************************************************************************/


/**************************************start 退还押金状态；0：已取消；1：待退还押金；2：已退还押金；3：无需退还*************************************************/

    const depositUnfreezeStatusCancel = 1;

    const depositUnfreezeStatusUnpayed = 2;

    const depositUnfreezeStatusPayd = 3;

    const depositUnfreezeStatusNoPay = 4;

/***************************************end  *****************************************************************************/


    /**************************************start 退款状态 0：已取消；1：待退款；2：已退款；3：无需退款*************************************************/

    const refundCancel = 1;

    const refundUnpayed = 2;

    const refundPayd = 3;

    const refundNoPay = 4;

    /***************************************end  *****************************************************************************/


    /**************************************start 清算的状态 0：已取消；1：待索赔支付；2：待扣押金；3：待退还押金；4：待退款；5：清算已完成*************************************************/

    const orderCleaningCancel = 1;

    const orderCleaningUnpayed = 2;

    const orderCleaningDeposit = 3;

    const orderCleaningUnfreeze = 4;

    const orderCleaningUnRefund = 5;

    const orderCleaningComplete= 6;

    /***************************************end  *****************************************************************************/



    /**
     * 业务类型列表
     * @return array    业务类型列表
     */
    public static function getBusinessTypeList(){
        return [
            self::businessTypeReturn => '退货',
            self::businessTypeRefund => '退款',
            self::businessTypeReturnGoods => '还机',
            self::businessTypeBuy => '买断',
            self::businessTypeRent => '续租',
            self::businessTypeExchangeGoods => '6换货',
        ];
    }


    /**
     * 业务类型值 转换成 状态名称
     * @param int $status   业务类型值
     * @return string 业务类型名称
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
     * @return array    索赔状态列表
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
     * @param int $status   索赔状态值
     * @return string 索赔状态名称
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
     * @param int $status   扣除押金状态值
     * @return string 扣除押金状态名称
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
     * @return array    清算的状态列表
     */
    public static function getOrderCleaningList(){
        return [
            self::orderCleaningCancel => '已取消',
            self::orderCleaningUnpayed => '待索赔支付',
            self::orderCleaningDeposit => '待扣押金',
            self::orderCleaningUnfreeze => '待退还押金',
            self::orderCleaningUnRefund => '待退款',
            self::orderCleaningComplete => '清算已完成',
        ];
    }


    /**
     * 清算的状态名称
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

