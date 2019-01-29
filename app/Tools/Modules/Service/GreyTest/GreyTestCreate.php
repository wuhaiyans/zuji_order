<?php
namespace App\Tools\Modules\Service\GreyTest;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Modules\Repository\Coupon\Coupon;
use App\Tools\Modules\Service\Coupon\CouponServiceInterface;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Repository\Coupon\CouponUser;
use App\Tools\Modules\Repository\GreyTest\GreyTestRepository;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponModelRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 创建灰度发布信息
 * @author Gaobo
 *
 */
class GreyTestCreate
{
    protected $GreyTestRepository = [];
    
    /**
     * 解析依赖注入 将灰度作为公共业务，此处应使用容器，注入interface接口，其他各业务实现此接口，并注册服务
     * @param GreyTestRepository $GreyTestRepository
     */
    public function __construct(GreyTestRepository $GreyTestRepository)
    {
        $this->GreyTestRepository  = $GreyTestRepository;
    }
    
    /**
     * 动作执行器
     * @param string $modelNo
     * @param string $mobile
     * @return bool
     */
    public function execute(string $modelNo , string $mobile) : bool
    {
        return $this->GreyTestRepository->create($mobile, $modelNo);
    }
}