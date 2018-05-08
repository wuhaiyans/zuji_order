<?php

/**
 * 退货申请单状态
 * @access public 
 * 
 * 
 */

namespace App\Lib;

/**
 * ReturnStatus 退货申请单状态类
 *
 * 
 */
class ReturnStatus {

    /***********退货商品损耗类型******************/
    /**
     * @var int 全新未拆封
     */
    const  OrderGoodsNew = 1;
    /**
     * @var int 已拆封已使用
     */
    const OrderGoodsIncomplete = 2;


    /**
     * @var int 无效状态（为订单表的状态默认值设计）
     * 【注意：】绝对不允许出现出现状态为0的记录（要求程序控制）
     */
    const ReturnInvalid = 0;

    /**
     * @var int 已创建退货申请（生效状态）（用户提交了退货申请，开始进入退货审核流程）【起点】
     */
    const ReturnCreated = 1;
    /**
     * @var int 待审核（等待审核员进行退货原因确认操作）【中间状态】
     */
    const ReturnWaiting = 2;
    
    /**
     * @var int 审核通过（审核员同意用户退货申请，进入收货流程）【终点】
     */
    const ReturnAgreed = 3;
    
    /**
     * @var int 审核拒绝（审核员未同意用户退货申请，并录入拒绝的原因，无后续操作）【终点】
     */
    const ReturnDenied = 4;
    /**
     * @var int 取消退货申请 【终点】
     */
    const ReturnCanceled = 5;
    /**
     * 
     * @var int 用户换货
     */
    const ReturnHuanhuo =6;
    
    public static function getStatusList(){
        return [
            self::ReturnCreated => '已申请退货',
            self::ReturnWaiting => '退货审核中',
            self::ReturnAgreed => '审核通过',
            self::ReturnDenied => '审核拒绝',
            self::ReturnCanceled => '退货已取消',
            self::ReturnHuanhuo =>'用户换货',
        ];
    }

    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    
    public static function getLostType(){
        return [
            self::OrderGoodsNew => '全新未拆封',
            self::OrderGoodsIncomplete => '已拆封已使用',
        ];
    }
    
    public static function getLostName($status){
        $list = self::getLostType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
}
