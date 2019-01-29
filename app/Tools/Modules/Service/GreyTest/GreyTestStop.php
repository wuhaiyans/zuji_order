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
 * 停止灰度发布
 * @author Gaobo
 */
class GreyTestStop
{
    protected $GreyTestGetByModelNo = [];
    
    //将灰度作为公共业务，此处应使用容器，注入interface接口，其他各业务实现此接口，并注册服务
    /**
     * 依赖注入
     * @param GreyTestGetByModelNo $GreyTestGetByModelNo
     */
    public function __construct(GreyTestGetByModelNo $GreyTestGetByModelNo)
    {
        $this->GreyTestGetByModelNo  = $GreyTestGetByModelNo;
    }
    
    public function execute(string $modelNo) : bool
    {
        $greyTest = $this->GreyTestGetByModelNo->execute($modelNo);
        return $greyTest->stop();
    }
}