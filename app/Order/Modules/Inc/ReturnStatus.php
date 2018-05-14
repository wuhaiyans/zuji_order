<?php

namespace App\Order\Modules\Inc;

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
    /***********业务类型******************/
    /**
     * @var int 退款业务
     */
    const  OrderTuiKuan = 1;
    /**
     * @var int 退货业务
     */
    const OrderTuiHuo = 2;
    /**
     * @var int 换货业务
     */
    const OrderHuanHuo = 3;
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
     * @var int 审核通过（等待审核员进行退货原因确认操作）【中间状态】
     */
    const ReturnAgreed = 2;
    
    /**
     * @var int 审核拒绝（审核员同意用户退货申请，进入收货流程）【终点】
     */
    const ReturnDenied = 3;
    
    /**
     * @var int 取消退货申请（审核员未同意用户退货申请，并录入拒绝的原因，无后续操作）【终点】
     */
    const ReturnCanceled = 4;
    /**
     * @var int 已收货 【终点】
     */
    const ReturnReceive = 5;
    //已退货
    const ReturnTuiHuo = 7;
    //已换货
    const ReturnHuanHuo = 8;
    //已退款
    const ReturnTuiKuan= 9;
    //退款中
    const ReturnTui= 10;

    
    public static function getStatusList(){
        return [
            self::ReturnInvalid => '无效状态',
            self::ReturnCreated => '提交申请',
            self::ReturnAgreed => '审核通过',
            self::ReturnDenied => '审核拒绝',
            self::ReturnCanceled => '取消退货申请',
            self::ReturnReceive =>'已收货',
            self::ReturnTuiHuo => '已退货',
            self::ReturnHuanHuo => '已换货',
            self::ReturnTuiKuan => '已退款',
            self::ReturnTui =>'退款中',
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
    public static function business_key(){
        return [
            self::OrderTuiKuan => '退款业务',
            self::OrderTuiHuo => '退货业务',
            self::OrderHuanHuo => '换货业务',
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
