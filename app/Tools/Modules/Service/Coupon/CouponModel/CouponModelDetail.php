<?php
namespace App\Tools\Modules\Service\Coupon\CouponModel;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Repository\GreyTest\GreyTest;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 获取优惠券模型详情 其他服务需要调用详情时统一依赖此服务
 * @author Gaobo
 */
class CouponModelDetail
{
    protected $couponModelRepository = [];

    /**
     * 注入repository依赖
     * @param CouponModelRepository $couponModelRepository
     */
    public function __construct(CouponModelRepository $couponModelRepository)
    {
        $this->couponModelRepository = $couponModelRepository;
    }
    
    /**
     * 动作执行器
     * @param string $modelNo
     * @return boolean|\App\Tools\Models\CouponModel|array
     */
    public function execute(string $modelNo)
    {
        if($modelNo){
            $model = $this->couponModelRepository->getDetailByModelNo($modelNo);
            set_apistatus(ApiStatus::CODE_0, '');
            if(!$model->toArray()){
                set_apistatus(ApiStatus::CODE_50000, '无此数据');
                return false;
            }
            return $model;
        }else{
            set_apistatus(ApiStatus::CODE_20001, '参数错误,ID必传');
            return [];
        }
    }
}