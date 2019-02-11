<?php
namespace App\Tools\Modules\Service\Coupon\CouponUser;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Checker\Checker;
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelDetail;

class CouponUserWriteOff
{
    protected $CouponUserDetail = [];
    protected $CouponModelDetail = [];
    
    public function __construct(CouponUserDetail $CouponUserDetail , CouponModelDetail $CouponModelDetail)
    {
        $this->CouponUserDetail = $CouponUserDetail;
        $this->CouponModelDetail = $CouponModelDetail;
    }
    
    public function execute(int $id , string $mobile)
    {
        $userCoupon = $this->CouponUserDetail->execute($id);//mobile
        if($userCoupon->toArray()){
            if($userCoupon->getAttribute('mobile') !== $mobile){
                set_apistatus(ApiStatus::CODE_50000 , '这可不是你的优惠券!');
                return [];
            }
            //获取模型详情
            $couponModel = $this->CouponModelDetail->execute($userCoupon->getAttribute('model_no'));
            $time = time();
            //检查是否可用
            $checker = new Checker();
            $checker->setCurrentTime($time);
            $checker->setUseStartTime($userCoupon->getAttribute('start_time'));
            $checker->setUseEndTime($userCoupon->getAttribute('end_time'));
            $checker->setUseStatus($userCoupon->getAttribute('status'));
            $checker->setModelStatus($couponModel->getAttribute('status'));
            $checker->checkingUse();
            if($checker->getCode() == ApiStatus::CODE_0){
                $userCoupon->setAttribute('status', CouponStatus::CouponStatusAlreadyUsed);
                $userCoupon->setAttribute('use_time', $time);
                if($userCoupon->save()){
                    set_apistatus(ApiStatus::CODE_0 , '');
                }else{
                    set_apistatus(ApiStatus::CODE_50000 , '保存失败');
                }
                return [];
            }
            set_apistatus($checker->getCode() , $checker->getMsg());
            return [];
        }
        set_apistatus(ApiStatus::CODE_50000 , '无此数据');
        return [];
    }
}