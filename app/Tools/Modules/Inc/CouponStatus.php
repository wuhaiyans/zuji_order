<?php
namespace App\Tools\Modules\Inc;

/**
 * CouponStatus 优惠券状态类
 */
class CouponStatus {
    
    //--------------------------------------------------------------------------------------------
    //--+ 优惠类型 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 固定金额
     */
    const CouponTypeFixed = 1;
    
    /**
     * @var int 租金百分比
     */
    const CouponTypePercentage = 2;
    
    /**
     * @var int 首月0租金
     */
    const CouponTypeFirstMonthRentFree = 3;
    
    /**
     * @var int 租金递减类型
     */
    const CouponTypeDecline = 4;
    /**
     * @var int 租金抵用券(分期使用)
     */
    const CouponTypeVoucher = 5;
    /**
     * @var int 满减
     */
    const CouponTypeFullReduction = 6;
    
    //--------------------------------------------------------------------------------------------
    //--+ 领取方式 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 主动领取
     */
    const SiteActive = 1;
    /**
     * @var int 被动领取
     */
    const SitePassive = 2;
    /**
     * @var int 站外发放
     */
    const SiteOut = 3;
    
    //--------------------------------------------------------------------------------------------
    //--+ 卡劵作用范围 -----------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 指定商品
     */
    const RangeSku = 1;
    /**
     * @var int 全场通用
     */
    const RangeAll = 2;
    
    //--------------------------------------------------------------------------------------------
    //--+ 卡劵发放状态 -----------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 草稿
     */
    const CouponTypeStatusRough = 0;
    /**
     * @var int 发放中
     */
    const CouponTypeStatusIssue = 1;
    /**
     * @var int 停止发放
     */
    const CouponTypeStatusStop = 2;
    /**
     * @var int 未发放
     */
    const CouponTypeStatusWei = 3;
    /**
     * @var int 已删除
     */
    const CouponTypeStatusDel = 3;
    /**
     * @var int 灰度发布
     */
    const CouponTypeStatusTest = 4;
    
    //--------------------------------------------------------------------------------------------
    //--+ 使用限制 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 0不限制
     */
    const UseRestrictionsNo = 0;
    /**
     * @var int 1限制
     */
    const UseRestrictionsYes = 1;
    
    //--------------------------------------------------------------------------------------------
    //--+ 优惠码状态 ------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 0未使用
     */
    const CouponStatusNotUsed = 0;
    /**
     * @var int 1已使用
     */
    const CouponStatusAlreadyUsed = 1;
    /**
     * @var int 2已过期
     */
    const CouponStatusExpire = 2;
    
    //--------------------------------------------------------------------------------------------
    //--+ 优惠码状态 ------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 1新人(已注册 未超过24小时之内)
     */
    const RangeUserNew = 1;
    /**
     * @var int 2老用户(已注册  超过24小时)
     */
    const RangeUserOld = 2;
    /**
     * @var int 3全部用户
     */
    const RangeUserScope = 3;
    /**
     * @var int 8指定用户
     */
    const DesignatedUser = 8;
    /**
     * @var int 9游客
     */
    const RangeUserVisitor = 9;
    
    //--------------------------------------------------------------------------------------------
    //--+ 选择商品关键字搜索条件 --------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 商品货号
     */
    const SelectGoodsSn = 1;
    /**
     * @var int 商品名称
     */
    const SelectGoodsName = 2;
    //--------------------------------------------------------------------------------------------
    //--+ 优惠券锁定 --------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------
    /**
     * @var int 0为锁定
     */
    const CouponLockWei = 0;
    /**
     * @var int 1已锁定
     */
    const CouponLockSuo = 1;
    
    
    /*
     * 卡劵类型转换汉字
     */
    public static function get_coupon_type_name( $num=null) {
        $type_arr = [
            self::CouponTypeFixed=>'固定金额劵',
            self::CouponTypePercentage=>'租金折扣劵',
            self::CouponTypeFirstMonthRentFree=>'首月0租金劵',
            //            self::CouponTypeDecline=>'租金递减劵',
            self::CouponTypeVoucher=>'租金抵用券',
            self::CouponTypeFullReduction=>'满减劵',
        ];
        if($num===null){
            return $type_arr;
        }else{
            $name = $type_arr[$num]?$type_arr[$num]:'无';
            return $name;
        }
    }
    
    /*
     * 卡劵领取方式
     */
    public static function get_coupon_type_site( $num=null) {
        $type_arr = [
            self::SiteActive=>'用户主动领取',
            self::SitePassive=>'用户被动领取',
            self::SiteOut=>'站外活动发放'
        ];
        if($num===null){
            return $type_arr;
        }else{
            $name = $type_arr[$num]?$type_arr[$num]:'无';
            return $name;
        }
    }
    
    /*
     * 卡劵发放状态
     */
    public static function get_coupon_type_status( $num=null) {
        $type_arr = [
            self::CouponTypeStatusRough=>'草稿',
            self::CouponTypeStatusWei=>'未发布',
            self::CouponTypeStatusIssue=>'发布中',
            self::CouponTypeStatusDel=>'已删除',
            self::CouponTypeStatusStop=>'停止发布',
            self::CouponTypeStatusTest=>'灰度测试中',
            
        ];
        if($num===null){
            return $type_arr;
        }else{
            $name = $type_arr[$num]?$type_arr[$num]:'无';
            return $name;
        }
    }
    
    /*
     * 转换优惠券状态
     */
    public static function get_coupon_status_name( $num=null) {
        $name_arr = [
            self::CouponStatusNotUsed=>'未使用',
            self::CouponStatusAlreadyUsed=>'已使用',
        ];
        if($num===null){
            return $name_arr;
        }else{
            $name = $name_arr[$num]?$name_arr[$num]:'无';
            return $name;
        }
    }
    
    /*
     * 转换发放用户范围
     */
    public static function get_coupon_range_name( $num=null ) {
        $name_arr = [
            self::RangeUserNew   => '新注册用户(24小时内未下单)',
            self::RangeUserOld   => '老用户(注册24小时以上)',
            self::DesignatedUser => '指定用户',
        ];
        if($num===null){
            return $name_arr;
        }else{
            $name = $name_arr[$num]?$name_arr[$num]:'无';
            return $name;
        }
    }
    
    public static function getCouponRangeScope($num=null)
    {
        $name_arr = [
            self::RangeUserScope => '全体用户',
        ];
        if($num===null){
            return $name_arr;
        }else{
            $name = $name_arr[$num]?$name_arr[$num]:'无';
            return $name;
        }
    }
    
    /*
     * 转换发放用户范围
     */
    public static function get_coupon_keyword_item( $num=null) {
        $name_arr = [
            self::SelectGoodsSn=>'商品货号',
            self::SelectGoodsName=>'商品名称',
        ];
        if($num===null){
            return $name_arr;
        }else{
            $name = $name_arr[$num]?$name_arr[$num]:'无';
            return $name;
        }
    }
    
}
