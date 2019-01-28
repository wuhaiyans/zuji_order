<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Lib\Tool\Tool;

/**
 * 取消停止发布
 * @author Gaobo
 */
class CouponModelUnPublish
{
    protected $CouponModelDetail = [];

    /**
     * 解析注入依赖类
     * @param CouponModelDetail $CouponModelDetail
     */
    public function __construct(CouponModelDetail $CouponModelDetail)
    {
        $this->CouponModelDetail = $CouponModelDetail;
    }
    
    /**
     * 动作执行器
     * @param string $modelNo
     * @return array
     */
    public function execute(string $modelNo) : array
    {
        $couponModel = $this->CouponModelDetail->execute($modelNo);
        if(!$couponModel){
            return [];
        }
        if($couponModel->status == CouponStatus::CouponTypeStatusIssue){
            $couponModel->status  = CouponStatus::CouponTypeStatusStop;
            set_apistatus(ApiStatus::CODE_0, '');
            if(!$couponModel->save()){
                set_apistatus(ApiStatus::CODE_50000, '优惠券保存失败');
            }
            return [];
        }
        set_apistatus(ApiStatus::CODE_50000, '优惠券状态错误');
        return [];
    }
}