<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Inc\CouponStatus;
use App\Lib\Tool\Tool;
use App\Tools\Modules\Service\GreyTest\GreyTestStop;

/**
 * 取消优惠券灰度发布
 * @author Gaobo
 *
 */
class CouponModelTestStop
{
    protected $CouponModelDetail = [];
    protected $GreyTestStop  = [];
    
    //将灰度作为公共业务，此处应使用容器，注入interface接口，其他各业务实现此接口，并注册服务
    /**
     * 依赖注入
     * @param CouponModelDetail $CouponModelDetail
     * @param GreyTestStop $GreyTestStop
     */
    public function __construct(CouponModelDetail $CouponModelDetail , GreyTestStop $GreyTestStop)
    {
        $this->CouponModelDetail = $CouponModelDetail;
        $this->GreyTestStop  = $GreyTestStop;
    }
    
    /**
     * 动作执行器
     * @param string $modelNo
     * @return bool
     */
    public function execute(string $modelNo) : array
    {
        //事务处理
        $couponModel = $this->CouponModelDetail->execute($modelNo);
        if(!$couponModel){
            return [];
        }
        if($couponModel->status == CouponStatus::CouponTypeStatusTest){
            DB::beginTransaction();
            $couponModel->status  = \App\Tool\Modules\Inc\CouponStatus::CouponTypeStatusRough;
            //停止灰度测试信息
            $ret = $this->GreyTestStop->execute($modelNo);
            if(!$ret){
                DB::rollBack();
                set_apistatus(ApiStatus::CODE_50000, '优惠券停止灰度发布失败');
                return [];
            }
            set_apistatus(ApiStatus::CODE_0, '');
            //保存优惠券模型状态
            if(!$couponModel->save()){
                DB::rollBack();
                set_apistatus(ApiStatus::CODE_50000, '优惠券保存失败');
                return [];
            }
            DB::commit();
            return [];
        }
        set_apistatus(ApiStatus::CODE_50000, '优惠券状态错误');
        return [];
    }
}