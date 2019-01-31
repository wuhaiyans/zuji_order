<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Inc\CouponStatus;
use App\Lib\Tool\Tool;
use App\Tools\Modules\Service\Coupon\CouponUser\CouponUserCreate;
use App\Tools\Modules\Service\GreyTest\GreyTestCreate;

/**
 * 灰度发布优惠券
 * @author Gaobo
 */
class CouponModelTestStart
{
    protected $CouponModelDetail = [];
    protected $CouponUserCreate  = [];
    protected $GreyTestCreate  = [];
    
    /**
     * 将灰度作为公共业务，此处应使用容器，注入interface接口，其他各业务实现此接口，并注册服务
     * @param CouponModelDetail $CouponModelDetail
     * @param CouponUserCreate $CouponUserCreate
     * @param GreyTestCreate $GreyTestCreate
     */
    public function __construct(CouponModelDetail $CouponModelDetail , CouponUserCreate $CouponUserCreate , GreyTestCreate $GreyTestCreate)
    {
        $this->CouponModelDetail = $CouponModelDetail;
        $this->CouponUserCreate  = $CouponUserCreate;
        $this->GreyTestCreate  = $GreyTestCreate;
    }
    
    /**
     * 动作执行器
     * @param string $modelNo
     * @param string $mobile
     * @return array
     */
    public function execute(string $modelNo , string $mobile) : array
    {
        //事务处理
        $couponModel = $this->CouponModelDetail->execute($modelNo);
        if(!$couponModel){
            return [];
        }
        if($couponModel->status == CouponStatus::CouponTypeStatusRough){
            DB::beginTransaction();
            $couponModel->status  = CouponStatus::CouponTypeStatusTest;
            $data = $couponModel->toArray();
            $ret = $this->CouponUserCreate->execute($data,$data['issue_num']);
            if(!$ret){
                DB::rollBack();
                set_apistatus(ApiStatus::CODE_50000, '优惠券CouponUserCreate失败');
                return [];
            }
            $ret = $this->GreyTestCreate->execute($modelNo, $mobile);
            if(!$ret){
                DB::rollBack();
                set_apistatus(ApiStatus::CODE_50000, '优惠券GreyTestCreate失败');
                return [];
            }
            if(!$couponModel->save()){
                DB::rollBack();
                set_apistatus(ApiStatus::CODE_50000, '优惠券保存失败');
            }
            DB::commit();
            set_apistatus(ApiStatus::CODE_0, '');
            return [];
        }
        set_apistatus(ApiStatus::CODE_50000, '优惠券状态错误');
        return [];
    }
}