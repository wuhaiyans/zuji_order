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
    //已发货
    const ReturnDelivery= 11;


    /***********退换货审核原因******************/
    /**
     * @var int 其他
     */
    const  ReturnOtherQuestion = 0;
    /**
     * @var int 设备问题
     */
    const ReturnGoodsQuestion = 1;
    /**
     * @var int 用户问题
     */
    const ReturnUserQuestion = 2;



    /***********检测结果******************/
    /**
     * @var int 待检测
     */
    const  ReturnEvaluation = 0;
    /**
     * @var int 检测合格
     */
    const ReturnEvaluationSuccess= 1;
    /**
     * @var int 检测不合格
     */
    const ReturnEvaluationFalse= 2;


    /***********业务类型******************/
    /**
     * @var int 线上业务
     */
    const  OnLineBusiness = 0;
    /**
     * @var int 线下业务
     */
    const UnderLineBusiness = 1;

    public static function getStatusList(){
        return [
            self::ReturnInvalid => '无效状态',
            self::ReturnCreated => '待审核',
            self::ReturnAgreed => '审核通过',
            self::ReturnDenied => '审核拒绝',
            self::ReturnCanceled => '已取消',
            self::ReturnReceive =>'已收货',
            self::ReturnTuiHuo => '已退货',
            self::ReturnHuanHuo => '已换货',
            self::ReturnTuiKuan => '已退款',
            self::ReturnTui =>'退款中',
            self::ReturnDelivery =>'已发货',

        ];
    }
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    public static function getReturnEvaluationList(){
        return [
                self::ReturnEvaluation => '待检测',
                self::ReturnEvaluationSuccess => '检测合格',
                self::ReturnEvaluationFalse => '检测不合格',
            ];
    }
	
	/**
	 * 获取分组后的状态列表
	 * @return array
	 */
    public static function getStatusGroupList(){
        return [
			//退款
            'refund'=>[
                self::ReturnCreated => self::getStatusName( self::ReturnCreated ),
                self::ReturnAgreed	=> self::getStatusName( self::ReturnAgreed ),
                self::ReturnDenied	=> self::getStatusName( self::ReturnDenied ),
                self::ReturnTuiKuan => self::getStatusName( self::ReturnTuiKuan ),
            ],
			//退换货
            'return'=>[
                self::ReturnCreated => self::getStatusName( self::ReturnCreated ),
                self::ReturnAgreed	=> self::getStatusName( self::ReturnAgreed ),
                self::ReturnDenied	=> self::getStatusName( self::ReturnDenied ),
                self::ReturnCanceled	=> self::getStatusName( self::ReturnCanceled ),
                self::ReturnReceive	=> self::getStatusName( self::ReturnReceive ),
                self::ReturnTui	=> self::getStatusName( self::ReturnTui ),
                self::ReturnTuiHuo	=> self::getStatusName( self::ReturnTuiHuo ),
                self::ReturnDelivery	=> self::getStatusName( self::ReturnDelivery ),
            ],
			//换货
            'barter'=>[
                self::ReturnCreated => self::getStatusName( self::ReturnCreated ),
                self::ReturnAgreed	=> self::getStatusName( self::ReturnAgreed ),
                self::ReturnDenied	=> self::getStatusName( self::ReturnDenied ),
                self::ReturnCanceled	=> self::getStatusName( self::ReturnCanceled ),
                self::ReturnReceive	=> self::getStatusName( self::ReturnReceive ),
                self::ReturnHuanHuo => self::getStatusName( self::ReturnHuanHuo ),
                self::ReturnDelivery	=> self::getStatusName( self::ReturnDelivery ),
            ],

        ];

    }
    /***********退货/换货原因问题******************/
    public static function getReturnQuestionList(){
        return [
			'1'  => '寄错手机型号',
			'2'  => '不想用了',
			'3'  => '收到时已经拆封',
			'4'  => '手机无法正常使用',
        ] ;

    }
	/**
	 * 获取退货原因名称
	 * @param int $status
	 * @return string
	 */
    public static function getReturnQuestionName($status){
        $list = self::getReturnQuestionList();
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
    public static function ReturnQuestion(){
        return [
            self::ReturnOtherQuestion => '其他',
            self::ReturnGoodsQuestion => '设备问题',
            self::ReturnUserQuestion => '用户问题 ',
        ];
    }
    public static function getQuestionName($status){
        $list = self::ReturnQuestion();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    public static function getLostName($status){
        $list = self::getLostType();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
    public static function getBusinessName($status){
        $list = self::business_key();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }
}
