<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Modules\Repository\Coupon\Coupon;
use App\Tools\Modules\Service\Coupon\CouponServiceInterface;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Repository\Coupon\CouponUser;
use App\Tools\Modules\Repository\GreyTest\GreyTest;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use \App\Tools\Modules\Inc;

/**
 * 删除优惠券
 * @author Gaobo
 *
 */
class CouponModelRemove
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
        if($couponModel->status == CouponStatus::CouponTypeStatusRough){
            $couponModel->status  = CouponStatus::CouponTypeStatusDel;
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