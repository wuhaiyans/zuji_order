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
use App\Tools\Modules\Service\Coupon\CouponUser\CouponUserCreate;
use App\Tool\Models\GreyTestModel;

/**
 * 发布优惠券
 * @author Gaobo
 *
 */
class CouponModelRePublish
{
    protected $CouponModelDetail = [];
    protected $CouponUserCreate  = [];

    /**
     * 解析注入依赖
     * @param CouponModelDetail $CouponModelDetail
     * @param CouponUserCreate $CouponUserCreate
     */
    public function __construct(CouponModelDetail $CouponModelDetail , CouponUserCreate $CouponUserCreate)
    {
        $this->CouponModelDetail = $CouponModelDetail;
        $this->CouponUserCreate  = $CouponUserCreate;
    }
    
    /**
     * 动作执行器
     * @param string $modelNo
     * @return array
     */
    public function execute(string $modelNo , int $num) : array 
    {
        $couponModel = $this->CouponModelDetail->execute($modelNo);
        if(!$couponModel){
            return [];
        }
        if($couponModel->status == CouponStatus::CouponTypeStatusIssue){
            if($num > 0){
                $num = $num > 10000 ? 10000 : $num;
            }
            $data = $couponModel->toArray();
            $couponModel->issue_num = $couponModel->issue_num + $num;
            $this->CouponUserCreate->execute($data,$couponModel->issue_num);
            if($couponModel->save()){
                set_apistatus(ApiStatus::CODE_0, '');
            }else{
                set_apistatus(ApiStatus::CODE_50000, '保存失败');
            }
            return [];
        }
        set_apistatus(ApiStatus::CODE_50000, '优惠券模型状态错误');
        return [];
    }
}